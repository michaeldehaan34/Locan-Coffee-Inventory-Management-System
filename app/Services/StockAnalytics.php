<?php

namespace App\Services;

use App\Models\Bahan;
use App\Models\StokMasuk;
use App\Models\UpdateStok;
use App\Models\GudangKirimStok;
use App\Models\BahanLimit;
use Illuminate\Database\Eloquent\Builder;

/**
 * Port dari modul Flask:
 *  - modules/update_stok_reader.py (Single Source of Truth)
 *  - modules/manager_dashboard.py
 *  - modules/history.py
 *  - modules/forecast.py
 *  - modules/bahan_limit.py
 *
 * Semua statistik dihitung secara dinamis dari tabel update_stok /
 * stok_masuk via Eloquent (query dioptimasi: hanya kolom aktif + latest row).
 */
class StockAnalytics
{
    public const DEFAULT_LIMIT_HABIS = 0.0;
    public const DEFAULT_LIMIT_TIPIS = 2.0;

    /**
     * Map kode bahan aktif -> [limit_habis, limit_tipis] (dari relasi bahan_limit).
     *
     * @param string $inventoryType 'gudang' atau 'coffee_shop'
     */
    public static function limitMap(string $inventoryType = 'coffee_shop'): array
    {
        $bahanList = Bahan::where('is_active', 1)
            ->select('id', 'kode')
            ->get();

        $limits = BahanLimit::where('inventory_type', $inventoryType)->get()->keyBy('bahan_id');

        $map = [];
        foreach ($bahanList as $b) {
            $lim = $limits->get($b->id);
            $map[$b->kode] = [
                $lim ? $lim->limit_habis : self::DEFAULT_LIMIT_HABIS,
                $lim ? $lim->limit_tipis : self::DEFAULT_LIMIT_TIPIS,
            ];
        }

        return $map;
    }

    /**
     * Map kode bahan aktif -> nama bahan (dari Master Barang).
     */
    public static function keyToLabel(string $inventoryType = null): array
    {
        $labels = Bahan::activeItems($inventoryType);
        $keyToLabel = [];
        foreach ($labels as $l) {
            $keyToLabel[$l['kode']] = $l['nama'];
        }

        return $keyToLabel;
    }

    /**
     * Map kode bahan aktif -> record bahan lengkap (dari Master Barang).
     */
    public static function activeMap(string $inventoryType = null): array
    {
        $bahanMap = Bahan::activeItems($inventoryType);
        $map = [];
        foreach ($bahanMap as $b) {
            $map[$b['kode']] = $b;
        }

        return $map;
    }

    /**
     * Baca seluruh baris update_stok (hanya kolom bahan aktif) sekali saja.
     */
    /**
     * Kode bahan aktif yang BENAR-BENAR memiliki kolom di tabel update_stok.
     *
     * Pencegahan QueryException: kolom transaksi disinkronkan lewat
     * MasterBahanController (ALTER TABLE), namun bisa saja tidak sinkron
     * (mis. penambahan bahan gagal saat ALTER). Memilih kolom yang tidak
     * ada akan memicu "SQLSTATE[42S22]: Column not found" (QueryException)
     * sehingga dashboard kosong/error. Kita intercept hanya kolom yang
     * sungguh ada, tanpa mengubah desain maupun menghapus widget apa pun.
     *
     * @return array<int, string>
     */
    public static function existingUpdateStokKeys(string $inventoryType = null): array
    {
        $active = Bahan::activeKeys($inventoryType);
        if (empty($active)) {
            return [];
        }

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('update_stok');
        $base = ['id', 'tanggal', 'shift', 'barista', 'created_at'];
        $bahanCols = array_diff($columns, $base);

        // Hanya kode aktif yang memiliki kolom nyata di update_stok.
        return array_values(array_intersect($active, $bahanCols));
    }

    public static function readUpdateStok(string $inventoryType = 'coffee_shop'): array
    {
        $keys = self::existingUpdateStokKeys($inventoryType);
        if (empty($keys)) {
            return ['has_data' => false, 'item_keys' => [], 'rows' => [], 'last_row' => null];
        }

        $select = array_merge(['id', 'tanggal', 'shift', 'barista', 'barista_id', 'created_at'], $keys);
        $rawRows = UpdateStok::query()->with('user')->where('inventory_type', $inventoryType)->select($select)->orderBy('tanggal')->orderBy('id')->get();

        $rows = [];
        foreach ($rawRows as $rec) {
            $values = [];
            foreach ($keys as $k) {
                $values[$k] = $rec->$k;
            }
            $waktuWib = $rec->created_at ? \Carbon\Carbon::parse($rec->created_at)->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i:s') . ' WIB' : '-';
            $rows[] = [
                'tanggal' => $rec->tanggal ? $rec->tanggal->format('Y-m-d') : '',
                'shift' => $rec->shift ?: '-',
                'barista' => $rec->user ? $rec->user->nama_lengkap : ($rec->barista ?: '-'),
                'barista_role' => $rec->user ? $rec->user->role : '',
                'waktu_wib' => $waktuWib,
                'created_at_raw' => $rec->created_at ? $rec->created_at->toDateTimeString() : null,
                'values' => $values,
            ];
        }

        $lastRaw = UpdateStok::query()->with('user')->where('inventory_type', $inventoryType)->select($select)->orderByDesc('tanggal')->orderByDesc('id')->first();
        $lastRow = null;
        if ($lastRaw) {
            $lastValues = [];
            foreach ($keys as $k) {
                $lastValues[$k] = $lastRaw->$k;
            }
            $waktuWib = $lastRaw->created_at ? \Carbon\Carbon::parse($lastRaw->created_at)->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i:s') . ' WIB' : '-';
            $lastRow = [
                'tanggal' => $lastRaw->tanggal ? $lastRaw->tanggal->format('Y-m-d') : '',
                'shift' => $lastRaw->shift ?: '-',
                'barista' => $lastRaw->user ? $lastRaw->user->nama_lengkap : ($lastRaw->barista ?: '-'),
                'barista_role' => $lastRaw->user ? $lastRaw->user->role : '',
                'waktu_wib' => $waktuWib,
                'created_at_raw' => $lastRaw->created_at ? $lastRaw->created_at->toDateTimeString() : null,
                'values' => $lastValues,
            ];
        }

        return [
            'has_data' => $rawRows->isNotEmpty(),
            'item_keys' => $keys,
            'rows' => $rows,
            'last_row' => $lastRow,
        ];
    }

    /**
     * Menghitung STOK Coffeeshop terkini secara dinamis berdasarkan UpdateStok terakhir 
     * dan total GudangKirimStok (yang diterima) setelah UpdateStok tersebut.
     * 
     * @return array<string, float> Map of kode bahan => global stock
     */
    public static function getCoffeeShopStockMap(): array
    {
        $keys = self::existingUpdateStokKeys('coffee_shop');
        $kodeToId = [];
        foreach (Bahan::activeItems('coffee_shop') as $b) {
            $kodeToId[$b['kode']] = $b['id'];
        }

        $result = [];

        // OPTIMASI: 1 Query untuk mendapatkan seluruh riwayat UpdateStok (memori)
        $allUpdateStok = UpdateStok::where('inventory_type', 'coffee_shop')->orderByDesc('tanggal')->orderByDesc('id')->get();

        // OPTIMASI: 1 Query untuk mendapatkan seluruh transaksi kirim stok yang diterima
        $acceptedTransfers = \App\Models\GudangKirimStokItem::select('gudang_kirim_stok_items.bahan_id', 'gudang_kirim_stok_items.jumlah', \Illuminate\Support\Facades\DB::raw('COALESCE(gudang_kirim_stok.received_at, gudang_kirim_stok.updated_at) as tx_updated_at'))
            ->join('gudang_kirim_stok', 'gudang_kirim_stok.id', '=', 'gudang_kirim_stok_items.gudang_kirim_stok_id')
            ->where('gudang_kirim_stok.status', 'diterima')
            ->where('gudang_kirim_stok.tujuan', 'coffee_shop')
            ->get();

        // OPTIMASI: 1 Query untuk mendapatkan seluruh transaksi ambil bahan gudang
        $ambilBahanTransfers = \App\Models\AmbilBahanGudangItem::select('ambil_bahan_gudang_items.bahan_id', 'ambil_bahan_gudang_items.jumlah', 'ambil_bahan_gudang.created_at as tx_updated_at')
            ->join('ambil_bahan_gudang', 'ambil_bahan_gudang.id', '=', 'ambil_bahan_gudang_items.ambil_bahan_gudang_id')
            ->where('ambil_bahan_gudang.inventory_type', 'coffee_shop')
            ->get();

        foreach ($keys as $k) {
            $bahanId = $kodeToId[$k] ?? null;
            if (!$bahanId) continue;

            $lastUpdate = $allUpdateStok->first(function ($item) use ($k) {
                return $item->{$k} !== null && $item->{$k} !== '';
            });
            
            $baseStock = 0.0;
            $t = null;
            if ($lastUpdate) {
                $baseStock = self::toFloat($lastUpdate->{$k}) ?? 0.0;
                $t = $lastUpdate->created_at;
            }

            $addedStock = 0.0;
            $tString = null;
            if ($t) {
                $tString = $t instanceof \Carbon\Carbon ? $t->format('Y-m-d H:i:s') : (string)$t;
            }

            foreach ($acceptedTransfers as $transfer) {
                if ($transfer->bahan_id == $bahanId) {
                    if ($tString) {
                        $txTime = $transfer->tx_updated_at instanceof \Carbon\Carbon 
                                    ? $transfer->tx_updated_at->format('Y-m-d H:i:s') 
                                    : (string)$transfer->tx_updated_at;
                        if ($txTime > $tString) {
                            $addedStock += (float) $transfer->jumlah;
                        }
                    } else {
                        $addedStock += (float) $transfer->jumlah;
                    }
                }
            }

            foreach ($ambilBahanTransfers as $transfer) {
                if ($transfer->bahan_id == $bahanId) {
                    if ($tString) {
                        $txTime = $transfer->tx_updated_at instanceof \Carbon\Carbon 
                                    ? $transfer->tx_updated_at->format('Y-m-d H:i:s') 
                                    : (string)$transfer->tx_updated_at;
                        if ($txTime > $tString) {
                            $addedStock += (float) $transfer->jumlah;
                        }
                    } else {
                        $addedStock += (float) $transfer->jumlah;
                    }
                }
            }

            $result[$k] = $baseStock + $addedStock;
        }

        return $result;
    }

    /**
     * Menghitung STOK KITCHEN terkini secara dinamis berdasarkan UpdateStok terakhir
     * dan transaksi Ambil Bahan Gudang untuk Kitchen.
     * 
     * @return array<string, float> Map of kode bahan => global stock
     */
    public static function getKitchenStockMap(): array
    {
        $keys = self::existingUpdateStokKeys('kitchen');
        $kodeToId = [];
        foreach (Bahan::activeItems('kitchen') as $b) {
            $kodeToId[$b['kode']] = $b['id'];
        }

        $result = [];

        $allUpdateStok = UpdateStok::where('inventory_type', 'kitchen')
            ->orderByDesc('tanggal')->orderByDesc('id')->get();

        $ambilBahanTransfers = \App\Models\AmbilBahanGudangItem::select('ambil_bahan_gudang_items.bahan_id', 'ambil_bahan_gudang_items.jumlah', 'ambil_bahan_gudang.created_at as tx_updated_at')
            ->join('ambil_bahan_gudang', 'ambil_bahan_gudang.id', '=', 'ambil_bahan_gudang_items.ambil_bahan_gudang_id')
            ->where('ambil_bahan_gudang.inventory_type', 'kitchen')
            ->get();

        $acceptedKitchenTransfers = \App\Models\GudangKirimStokItem::select('gudang_kirim_stok_items.bahan_id', 'gudang_kirim_stok_items.jumlah', \Illuminate\Support\Facades\DB::raw('COALESCE(gudang_kirim_stok.received_at, gudang_kirim_stok.updated_at) as tx_updated_at'))
            ->join('gudang_kirim_stok', 'gudang_kirim_stok.id', '=', 'gudang_kirim_stok_items.gudang_kirim_stok_id')
            ->where('gudang_kirim_stok.status', 'diterima')
            ->where('gudang_kirim_stok.tujuan', 'kitchen')
            ->get();

        foreach ($keys as $k) {
            $bahanId = $kodeToId[$k] ?? null;
            if (!$bahanId) continue;

            $lastUpdate = $allUpdateStok->first(function ($item) use ($k) {
                return $item->{$k} !== null && $item->{$k} !== '';
            });
            
            $baseStock = 0.0;
            $t = null;
            if ($lastUpdate) {
                $baseStock = self::toFloat($lastUpdate->{$k}) ?? 0.0;
                $t = $lastUpdate->created_at;
            }

            $addedStock = 0.0;
            $tString = null;
            if ($t) {
                $tString = $t instanceof \Carbon\Carbon ? $t->format('Y-m-d H:i:s') : (string)$t;
            }

            foreach ($ambilBahanTransfers as $transfer) {
                if ($transfer->bahan_id == $bahanId) {
                    if ($tString) {
                        $txTime = $transfer->tx_updated_at instanceof \Carbon\Carbon 
                                    ? $transfer->tx_updated_at->format('Y-m-d H:i:s') 
                                    : (string)$transfer->tx_updated_at;
                        if ($txTime > $tString) {
                            $addedStock += (float) $transfer->jumlah;
                        }
                    } else {
                        $addedStock += (float) $transfer->jumlah;
                    }
                }
            }

            foreach ($acceptedKitchenTransfers as $transfer) {
                if ($transfer->bahan_id == $bahanId) {
                    if ($tString) {
                        $txTime = $transfer->tx_updated_at instanceof \Carbon\Carbon 
                                    ? $transfer->tx_updated_at->format('Y-m-d H:i:s') 
                                    : (string)$transfer->tx_updated_at;
                        if ($txTime > $tString) {
                            $addedStock += (float) $transfer->jumlah;
                        }
                    } else {
                        $addedStock += (float) $transfer->jumlah;
                    }
                }
            }

            $result[$k] = $baseStock + $addedStock;
        }

        return $result;
    }

    /**
     * Menghitung STOK GUDANG terkini secara dinamis.
     * Saldo = Total Stok Masuk (supplier) - Total Kirim Stok (Gudang -> CS).
     * Kirim stok yang berstatus 'pending' maupun 'diterima' tetap memotong stok gudang.
     */
    public static function getGudangStockMap(): array
    {
        $keys = self::existingUpdateStokKeys();
        $kodeToId = [];
        foreach (Bahan::activeItems() as $b) {
            $kodeToId[$b['kode']] = $b['id'];
        }

        $result = [];

        // OPTIMASI: 1 Query total barang keluar
        $activeIds = array_filter($kodeToId);
        $totalOuts = [];
        $totalAmbil = [];
        if (!empty($activeIds)) {
            $totalOuts = \App\Models\GudangKirimStokItem::whereIn('bahan_id', $activeIds)
                ->select('bahan_id', \Illuminate\Support\Facades\DB::raw('SUM(jumlah) as total_out'))
                ->groupBy('bahan_id')
                ->pluck('total_out', 'bahan_id')
                ->toArray();

            $totalAmbil = \App\Models\AmbilBahanGudangItem::whereIn('bahan_id', $activeIds)
                ->select('bahan_id', \Illuminate\Support\Facades\DB::raw('SUM(jumlah) as total_ambil'))
                ->groupBy('bahan_id')
                ->pluck('total_ambil', 'bahan_id')
                ->toArray();
        }

        // OPTIMASI: 1 Query baca semua StokMasuk
        $allStokMasuk = empty($keys) ? collect() : StokMasuk::select($keys)->get();

        foreach ($keys as $k) {
            $bahanId = $kodeToId[$k] ?? null;
            if (!$bahanId) continue;

            $totalIn = $allStokMasuk->filter(function($item) use ($k) {
                return $item->{$k} !== null && $item->{$k} !== '';
            })->sum(function($item) use ($k) {
                return (float) $item->{$k};
            });

            $totalOut = (float) ($totalOuts[$bahanId] ?? 0);
            $totalA = (float) ($totalAmbil[$bahanId] ?? 0);

            $result[$k] = $totalIn - $totalOut - $totalA;
        }

        return $result;
    }


    public static function classify($val, float $limitHabis, float $limitTipis): string
    {
        $v = self::toFloat($val);
        if ($v === null || $v <= $limitHabis) {
            return 'habis';
        }
        if ($v <= $limitTipis) {
            return 'tipis';
        }

        return 'aman';
    }

    public static function toFloat($val): ?float
    {
        if ($val === null || $val === '') {
            return null;
        }
        $s = str_replace(',', '.', (string) $val);
        if (! is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }

    public static function formatNumber($v): string
    {
        if ($v === null || $v === '') {
            return '-';
        }
        $f = (float) $v;
        if ($f == (int) $f) {
            return (string) (int) $f;
        }

        return rtrim(rtrim(sprintf('%g', $f), '0'), '.');
    }

    public static function displayDate(string $iso): string
    {
        try {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $iso)->format('d-m-Y');
        } catch (\Throwable $e) {
            return $iso;
        }
    }

    /**
     * Ringkasan status stok dari stok terakhir (Dashboard Analytics).
     */
    public static function summaryStats(array $data, string $inventoryType = 'coffee_shop'): array
    {
        $summary = ['aman' => 0, 'tipis' => 0, 'habis' => 0];
        $last = $data['last_row'] ?? null;
        if (! $last) {
            return $summary;
        }
        $limitMap = self::limitMap($inventoryType);
        foreach ($data['item_keys'] as $key) {
            [$lh, $lt] = $limitMap[$key] ?? [self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS];
            $summary[self::classify($last['values'][$key] ?? null, $lh, $lt)]++;
        }

        return $summary;
    }

    public static function topHabis(array $data, ?array $limitMap = null, ?array $keyToLabel = null, int $limit = 10, string $inventoryType = 'coffee_shop'): array
    {
        $counter = [];
        $limitMap = $limitMap ?? self::limitMap($inventoryType);
        $keyToLabel = $keyToLabel ?? self::keyToLabel();
        foreach ($data['rows'] as $row) {
            foreach ($data['item_keys'] as $key) {
                [$lh, $lt] = $limitMap[$key] ?? [self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS];
                if (self::classify($row['values'][$key] ?? null, $lh, $lt) === 'habis') {
                    $label = $keyToLabel[$key] ?? $key;
                    $counter[$label] = ($counter[$label] ?? 0) + 1;
                }
            }
        }
        arsort($counter);

        $result = [];
        $rank = 1;
        foreach ($counter as $name => $cnt) {
            if ($rank > $limit) {
                break;
            }
            $result[] = ['rank' => $rank, 'nama_barang' => $name, 'jumlah' => $cnt];
            $rank++;
        }

        return $result;
    }

    public static function topTipis(array $data, ?array $limitMap = null, ?array $keyToLabel = null, int $limit = 10, string $inventoryType = 'coffee_shop'): array
    {
        $counter = [];
        $limitMap = $limitMap ?? self::limitMap($inventoryType);
        $keyToLabel = $keyToLabel ?? self::keyToLabel();
        foreach ($data['rows'] as $row) {
            foreach ($data['item_keys'] as $key) {
                [$lh, $lt] = $limitMap[$key] ?? [self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS];
                if (self::classify($row['values'][$key] ?? null, $lh, $lt) === 'tipis') {
                    $label = $keyToLabel[$key] ?? $key;
                    $counter[$label] = ($counter[$label] ?? 0) + 1;
                }
            }
        }
        arsort($counter);

        $result = [];
        $rank = 1;
        foreach ($counter as $name => $cnt) {
            if ($rank > $limit) {
                break;
            }
            $result[] = ['rank' => $rank, 'nama_barang' => $name, 'jumlah' => $cnt];
            $rank++;
        }

        return $result;
    }

    public static function aktivitasBarista(array $data): array
    {
        $counter = [];
        foreach ($data['rows'] as $row) {
            $nama = trim((string) $row['barista']);
            if ($nama !== '') {
                $counter[$nama] = ($counter[$nama] ?? 0) + 1;
            }
        }
        arsort($counter);

        $result = [];
        $no = 1;
        foreach ($counter as $name => $cnt) {
            $result[] = ['no' => $no, 'nama_barista' => $name, 'jumlah' => $cnt];
            $no++;
        }

        return $result;
    }

    /**
     * Ringkas seluruh metrik Dashboard Analytics.
     * Mendukung pemisahan Gudang dan Coffeeshop.
     *
     * @param string $inventoryType 'gudang' atau 'coffee_shop'
     */
    public static function dashboard(string $inventoryType = 'coffee_shop'): array
    {
        $limitMap = self::limitMap($inventoryType);
        $labels = Bahan::activeItems($inventoryType);
        $keyToLabel = [];
        foreach ($labels as $l) {
            $keyToLabel[$l['kode']] = $l['nama'];
        }

        $summary = ['aman' => 0, 'tipis' => 0, 'habis' => 0];
        
        if ($inventoryType === 'gudang') {
            $stockMap = self::getGudangStockMap();
            $hasData = count($stockMap) > 0;
            $rows = [];
        } elseif ($inventoryType === 'kitchen') {
            $stockMap = self::getKitchenStockMap();
            $data = self::readUpdateStok('kitchen');
            $hasData = $data['has_data'];
            $rows = $hasData ? $data['rows'] : [];
        } else {
            $stockMap = self::getCoffeeShopStockMap();
            $data = self::readUpdateStok('coffee_shop');
            $hasData = $data['has_data'];
            $rows = $hasData ? $data['rows'] : [];
        }

        $stockFormatted = [];
        $keys = self::existingUpdateStokKeys($inventoryType);
        foreach ($keys as $key) {
            $gStock = $stockMap[$key] ?? 0.0;
            [$lh, $lt] = $limitMap[$key] ?? [self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS];
            $status = self::classify($gStock, $lh, $lt);
            $summary[$status]++;
            
            $label = $keyToLabel[$key] ?? $key;
            $stockFormatted[] = [
                'kode' => $key,
                'nama' => $label,
                'stok' => self::formatNumber($gStock),
                'satuan' => (Bahan::where('kode', $key)->first()->satuan ?? 'pcs'),
                'status' => $status
            ];
        }

        $habisCounter = [];
        $tipisCounter = [];
        $aktivitasCounter = [];
        
        if (in_array($inventoryType, ['coffee_shop', 'kitchen']) && $hasData) {
            foreach ($rows as $row) {
                foreach ($keys as $key) {
                    [$lh, $lt] = $limitMap[$key] ?? [self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS];
                    $status = self::classify($row['values'][$key] ?? null, $lh, $lt);
                    $label = $keyToLabel[$key] ?? $key;
                    if ($status === 'habis') {
                        $habisCounter[$label] = ($habisCounter[$label] ?? 0) + 1;
                    } elseif ($status === 'tipis') {
                        $tipisCounter[$label] = ($tipisCounter[$label] ?? 0) + 1;
                    }
                }
                $nama = trim((string) $row['barista']);
                if ($nama !== '') {
                    $aktivitasCounter[$nama] = ($aktivitasCounter[$nama] ?? 0) + 1;
                }
            }
        }

        arsort($habisCounter);
        arsort($tipisCounter);
        arsort($aktivitasCounter);

        $topHabis = [];
        $rank = 1;
        foreach ($habisCounter as $name => $cnt) {
            $topHabis[] = ['rank' => $rank, 'nama_barang' => $name, 'jumlah' => $cnt];
            $rank++;
        }

        $topTipis = [];
        $rank = 1;
        foreach ($tipisCounter as $name => $cnt) {
            $topTipis[] = ['rank' => $rank, 'nama_barang' => $name, 'jumlah' => $cnt];
            $rank++;
        }

        $topAktivitas = [];
        $no = 1;
        foreach ($aktivitasCounter as $name => $cnt) {
            $topAktivitas[] = ['no' => $no, 'nama_barista' => $name, 'jumlah' => $cnt];
            $no++;
        }

        return [
            'has_data' => $hasData,
            'bahan_aman' => $summary['aman'],
            'bahan_tipis' => $summary['tipis'],
            'bahan_habis' => $summary['habis'],
            'top_barang_habis' => $topHabis,
            'top_barang_tipis' => $topTipis,
            'top_aktivitas_barista' => $topAktivitas,
            'global_stock' => $stockFormatted,
        ];
    }

    /**
     * Forecast kebutuhan & estimasi pembelian untuk periode terpilih.
     */
    public static function forecast(?string $tanggalAwal = null, ?string $tanggalAkhir = null, ?array $data = null, ?array $limitMap = null, string $inventoryType = 'coffee_shop'): array
    {
        $data = $data ?? self::readUpdateStok($inventoryType);
        $limitMap = $limitMap ?? self::limitMap($inventoryType);
        $result = [
            'has_data' => $data['has_data'],
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
            'periode_valid' => false,
            'jumlah_hari' => 0,
            'items' => [],
            'items_tree' => [],
            'total_kebutuhan' => 0,
            'total_estimasi_pembelian' => 0,
        ];

        if (! ($tanggalAwal && $tanggalAkhir)) {
            // Kedua tanggal wajib diisi untuk menghitung forecast.
            return $result;
        }
        if (! is_valid_date($tanggalAwal) || ! is_valid_date($tanggalAkhir)) {
            // Tanggal tidak valid (format selain Y-m-d / tanggal tidak ada).
            return $result;
        }
        if ($tanggalAwal > $tanggalAkhir) {
            // Tanggal awal melebihi tanggal akhir.
            return $result;
        }

        try {
            $d1 = \Carbon\Carbon::createFromFormat('Y-m-d', $tanggalAwal);
            $d2 = \Carbon\Carbon::createFromFormat('Y-m-d', $tanggalAkhir);
            $result['jumlah_hari'] = $d1->diffInDays($d2) + 1;
        } catch (\Throwable $e) {
            return $result;
        }

        $rows = array_filter($data['rows'], fn ($r) => $tanggalAwal <= $r['tanggal'] && $r['tanggal'] <= $tanggalAkhir);
        usort($rows, fn ($a, $b) => $a['tanggal'] <=> $b['tanggal']);

        $result['periode_valid'] = true;
        $current = $data['last_row'];

        $totalKebutuhan = 0.0;
        $totalPembelian = 0.0;
        $labelToGroup = self::labelToGroup();

        foreach ($data['item_keys'] as $key) {
            [$lh, $lt] = $limitMap[$key] ?? [self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS];

            $consumption = 0.0;
            $prev = null;
            foreach ($rows as $r) {
                $v = self::toFloat($r['values'][$key] ?? null);
                if ($v === null) {
                    $v = 0.0;
                }
                if ($prev !== null && $prev > $v) {
                    $consumption += ($prev - $v);
                }
                $prev = $v;
            }

            $kebutuhan = round($consumption, 2);
            $cur = $current ? self::toFloat($current['values'][$key] ?? null) : null;
            if ($cur === null) {
                $cur = 0.0;
            }
            $estimasi = round(max(0.0, $kebutuhan - $cur), 2);
            $status = $cur > $lt ? 'aman' : 'perlu_dibeli';

            $totalKebutuhan += $kebutuhan;
            $totalPembelian += $estimasi;

            $label = $labelToGroup[$key]['label'] ?? $key;
            $result['items'][] = [
                'nama_barang' => $label,
                'stok_sekarang' => $cur,
                'kebutuhan' => $kebutuhan,
                'estimasi_pembelian' => $estimasi,
                'status' => $status,
                'limit_habis' => $lh,
                'limit_tipis' => $lt,
                'kode' => $key,
            ];
        }

        $result['total_kebutuhan'] = round($totalKebutuhan, 2);
        $result['total_estimasi_pembelian'] = round($totalPembelian, 2);

        // Susun hierarki Kategori -> Kelompok -> Barang
        $tree = [];
        foreach ($result['items'] as $it) {
            $g = $labelToGroup[$it['kode']] ?? ['kategori' => 'Lainnya', 'kelompok' => 'Lainnya'];
            $kat = $g['kategori'];
            $kel = $g['kelompok'];
            $tree[$kat][$kel][] = $it;
        }
        $orderKat = ['Bahan Baku Bar', 'Bahan Baku Kitchen', 'Equipment'];
        $itemsTree = [];
        foreach ($orderKat as $kat) {
            if (isset($tree[$kat])) {
                $itemsTree[] = ['kategori' => $kat, 'kelompok_list' => self::buildKelompok($tree[$kat])];
                unset($tree[$kat]);
            }
        }
        foreach ($tree as $kat => $kelDict) {
            $itemsTree[] = ['kategori' => $kat, 'kelompok_list' => self::buildKelompok($kelDict)];
        }
        $result['items_tree'] = $itemsTree;

        return $result;
    }

    private static function buildKelompok(array $kelDict): array
    {
        $list = [];
        foreach ($kelDict as $kel => $items) {
            $list[] = ['kelompok' => $kel, 'items' => $items];
        }

        return $list;
    }

    /**
     * Map kode bahan -> [kategori, kelompok, label].
     */
    public static function labelToGroup(): array
    {
        $rows = Bahan::select('kode', 'nama', 'kategori', 'kelompok')->get();
        $map = [];
        foreach ($rows as $r) {
            $map[$r->kode] = [
                'kategori' => $r->kategori ?: 'Lainnya',
                'kelompok' => $r->kelompok ?: 'Lainnya',
                'label' => $r->nama,
            ];
        }

        return $map;
    }

    /**
     * Riwayat Stok Masuk yang dikelompokkan per transaksi (untuk tabel + detail).
     */
    public static function groupedStokMasuk(?string $tglAwal, ?string $tglAkhir, ?string $shift, ?string $barista, ?string $barang): array
    {
        $keys = Bahan::activeKeys();

        $q = StokMasuk::query()->with('user')->select(array_merge(['id', 'tanggal', 'shift', 'barista', 'barista_id', 'created_at'], $keys));
        if ($tglAwal) {
            $q->where('tanggal', '>=', $tglAwal);
        }
        if ($tglAkhir) {
            $q->where('tanggal', '<=', $tglAkhir);
        }
        if ($shift) {
            $q->where('shift', $shift);
        }
        if ($barista) {
            $q->where('barista', 'like', '%'.$barista.'%');
        }
        self::applyBarangNameFilter($q, $barang);
        $raw = $q->orderByDesc('tanggal')->orderByDesc('id')->get();

        $transactions = [];
        foreach ($raw as $rec) {
            // Item diambil via relasi Eloquent ke Master Barang (StokMasuk::bahan()),
            // sehingga nama, satuan, kelompok, dan kategori otomatis mengikuti
            // tabel bahan (tidak ada data hardcode).
            $items = [];
            foreach ($rec->itemsFromMaster() as $it) {
                $items[] = [
                    'label' => $it['nama'],
                    'jumlah' => self::formatNumber($it['jumlah']),
                    'satuan' => $it['satuan'],
                    'kelompok' => $it['kelompok'],
                    'kategori' => $it['kategori'],
                    'catatan' => '-',
                ];
            }
            $tanggalIso = $rec->tanggal ? $rec->tanggal->format('Y-m-d') : '';
            $jam = $rec->created_at ? $rec->created_at->timezone('Asia/Jakarta')->format('H:i') : '-';
            $waktuWib = $rec->created_at ? \Carbon\Carbon::parse($rec->created_at)->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i:s') . ' WIB' : '-';
            $transactions[] = [
                'id' => $rec->id,
                'tanggal' => $tanggalIso,
                'tanggal_display' => self::displayDate($tanggalIso),
                'jam' => $jam,
                'shift' => $rec->shift ?: '-',
                'barista' => $rec->user ? $rec->user->nama_lengkap : ($rec->barista ?: '-'),
                'barista_role' => $rec->user ? $rec->user->role : '',
                'waktu_wib' => $waktuWib,
                'jumlah_item' => count($items),
                'items' => $items,
            ];
        }

        return $transactions;
    }

    /**
     * Terapkan filter pencarian Nama Barang (partial, case-insensitive)
     * langsung ke query Eloquent, meniru modules/repository.py ->
     * build_item_name_clause() pada Flask.
     *
     * Karena nama barang disimpan sebagai NAMA KOLOM (bukan nilai), kita
     * mencocokkan keyword terhadap daftar (kode, nama) bahan aktif, lalu
     * hanya mengikutkan transaksi yang kolom bersangkutan TERISI.
     *
     * Jika keyword tidak cocok dengan nama bahan manapun -> hasil kosong.
     */
    public static function applyBarangNameFilter(Builder $query, ?string $barang): void
    {
        if (! $barang || trim($barang) === '') {
            return;
        }

        $kw = strtolower(trim($barang));
        $matchedKeys = [];
        foreach (Bahan::activeItems() as $item) {
            if (str_contains(strtolower((string) ($item['nama'] ?? '')), $kw)) {
                $matchedKeys[] = $item['kode'];
            }
        }

        if (empty($matchedKeys)) {
            // Keyword tidak cocok dengan nama bahan manapun -> hasil kosong.
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($q) use ($matchedKeys) {
            foreach ($matchedKeys as $k) {
                $q->orWhere(function ($qq) use ($k) {
                    $qq->whereNotNull($k)->where($k, '<>', '');
                });
            }
        });
    }

    public static function stokMasukStats(array $transactions): array
    {
        $today = now()->format('Y-m-d');
        $weekAgo = now()->subDays(7)->format('Y-m-d');
        $monthAgo = now()->subDays(30)->format('Y-m-d');

        $stats = ['total' => count($transactions), 'today' => 0, 'week' => 0, 'month' => 0];
        foreach ($transactions as $t) {
            if ($t['tanggal'] === $today) {
                $stats['today']++;
            }
            if ($t['tanggal'] >= $weekAgo) {
                $stats['week']++;
            }
            if ($t['tanggal'] >= $monthAgo) {
                $stats['month']++;
            }
        }

        return $stats;
    }

    /**
     * Riwayat Update Stok (flat per transaksi) untuk tabel + detail + export.
     */
    public static function allUpdateStok(?string $barangKeyword = null, ?string $inventoryType = null): array
    {
        $keys = Bahan::activeKeys();
        $bahanMap = Bahan::activeItems();
        $map = [];
        foreach ($bahanMap as $b) {
            $map[$b['kode']] = $b;
        }

        $q = UpdateStok::query()->with('user')->select(array_merge(['id', 'tanggal', 'shift', 'barista', 'barista_id', 'created_at', 'inventory_type'], $keys));
        
        if ($inventoryType) {
            $q->where('inventory_type', $inventoryType);
        }

        if ($barangKeyword) {
            $q->where(function ($query) use ($keys, $barangKeyword) {
                foreach ($keys as $k) {
                    $query->orWhere($k, 'like', '%'.$barangKeyword.'%');
                }
            });
        }
        $raw = $q->orderByDesc('tanggal')->orderByDesc('id')->get();

        $records = [];
        foreach ($raw as $rec) {
            $items = [];
            $filled = 0;
            foreach ($keys as $k) {
                $rawVal = $rec->$k;
                $b = $map[$k] ?? null;
                $label = $b['nama'] ?? $k;
                $isFilled = $rawVal !== null && $rawVal !== '';
                $status = self::classify($rawVal, self::DEFAULT_LIMIT_HABIS, self::DEFAULT_LIMIT_TIPIS);
                                if ($isFilled) {
                    $filled++;
                }
                $items[] = [
                    'label' => $label,
                    'kategori' => $b['kategori'] ?? '-',
                    'kelompok' => $b['kelompok'] ?? '-',
                    'satuan' => $b['satuan'] ?? '-',
                    'value' => $isFilled ? $rawVal : '-',
                    'status' => $status,
                ];
            }
            $tanggalIso = $rec->tanggal ? $rec->tanggal->format('Y-m-d') : '';
            $waktuWib = $rec->created_at ? \Carbon\Carbon::parse($rec->created_at)->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i:s') . ' WIB' : '-';
            $records[] = [
                'id' => $rec->id,
                'tanggal' => $tanggalIso,
                'tanggal_display' => self::displayDate($tanggalIso),
                'shift' => $rec->shift ?: '-',
                'barista' => $rec->user ? $rec->user->nama_lengkap : ($rec->barista ?: '-'),
                'barista_role' => $rec->user ? $rec->user->role : '',
                'waktu_wib' => $waktuWib,
                'jumlah_item' => $filled,
                'items' => $items,
            ];
        }

        return $records;
    }

    public static function updateStokStats(array $records): array
    {
        $today = now()->format('Y-m-d');
        $weekAgo = now()->subDays(7)->format('Y-m-d');
        $monthAgo = now()->subDays(30)->format('Y-m-d');

        $stats = ['total' => count($records), 'today' => 0, 'week' => 0, 'month' => 0];
        foreach ($records as $r) {
            if ($r['tanggal'] === $today) {
                $stats['today']++;
            }
            if ($r['tanggal'] >= $weekAgo) {
                $stats['week']++;
            }
            if ($r['tanggal'] >= $monthAgo) {
                $stats['month']++;
            }
        }

        return $stats;
    }

    /**
     * Perbandingan stok dua tanggal bebas.
     */
    public static function comparisonForDates(?string $tglAwal, ?string $tglPembanding): array
    {
        $result = [
            'requested' => true,
            'has_data' => false,
            'tanggal_valid' => true,
            'tanggal_awal' => '-',
            'tanggal_pembanding' => '-',
            'items' => [],
        ];

        $tglAwalN = $tglAwal ?: null;
        $tglPembandingN = $tglPembanding ?: null;
        $result['tanggal_awal'] = $tglAwalN ? self::displayDate($tglAwalN) : '-';
        $result['tanggal_pembanding'] = $tglPembandingN ? self::displayDate($tglPembandingN) : '-';

        if ($tglAwalN && $tglPembandingN && $tglAwalN > $tglPembandingN) {
            $result['tanggal_valid'] = false;

            return $result;
        }

        $keys = Bahan::activeKeys();
        $bahanMap = Bahan::activeItems();
        $map = [];
        foreach ($bahanMap as $b) {
            $map[$b['kode']] = $b;
        }

        $rowAwal = UpdateStok::query()->select($keys)->where('tanggal', $tglAwalN)->orderByDesc('id')->first();
        $rowPembanding = UpdateStok::query()->select($keys)->where('tanggal', $tglPembandingN)->orderByDesc('id')->first();

        if (! $rowAwal || ! $rowPembanding) {
            return $result;
        }

        $result['has_data'] = true;
        foreach ($keys as $k) {
            $b = $map[$k] ?? null;
            $label = $b['nama'] ?? $k;
            $unit = $b['satuan'] ?? 'pcs';
            $vAwal = self::toFloat($rowAwal->$k);
            $vPembanding = self::toFloat($rowPembanding->$k);
            $awalVal = $vAwal ?? 0.0;
            $pembandingVal = $vPembanding ?? 0.0;
            $selisih = round($pembandingVal - $awalVal, 2);
            $status = $selisih > 0 ? 'bertambah' : ($selisih < 0 ? 'berkurang' : 'tetap');
            $result['items'][] = [
                'label' => $label,
                'unit' => $unit,
                'stok_awal' => self::formatNumber($vAwal),
                'stok_pembanding' => self::formatNumber($vPembanding),
                'selisih' => $selisih,
                'status' => $status,
            ];
        }

        return $result;
    }
}