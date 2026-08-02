<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterBahanRequest;
use App\Models\Bahan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Master Barang (Bahan) - Tahap 6 migrasi dari Flask ke Laravel 12.
 */
class MasterBahanController extends Controller
{
    /**
     * Daftar tabel transaksi yang kolomnya mengikuti kode bahan.
     */
    private array $transactionTables = ['stok_masuk', 'update_stok'];

    /**
     * Halaman daftar Master Barang.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $query = Bahan::query()
            ->select(['id', 'kode', 'nama', 'kategori', 'kelompok', 'satuan', 'urutan', 'is_active']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                  ->orWhere('kode', 'like', '%' . $search . '%');
            });
        }

        $bahan_list = $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('manager.master-bahan.index', [
            'title' => 'Master Barang',
            'bahan_list' => $bahan_list,
            'search' => $search,
        ]);
    }

    /**
     * Halaman detail Barang (full page info, bukan modal).
     */
    public function detail(int $id): View
    {
        $bahan = Bahan::findOrFail($id);

        return view('manager.master-bahan.detail', [
            'title' => 'Detail Barang',
            'bahan' => $bahan,
        ]);
    }

    /**
     * Halaman form tambah Barang.
     */
    public function create(): View
    {
        return view('manager.master-bahan.create', [
            'title' => 'Tambah Barang',
            'kategori_list' => config('lotra.kategori_list', []),
            'satuan_list' => config('lotra.satuan_list', []),
        ]);
    }

    /**
     * Halaman form edit Barang.
     */
    public function edit(int $id): View
    {
        $bahan = Bahan::findOrFail($id);

        return view('manager.master-bahan.edit', [
            'title' => 'Edit Barang',
            'bahan' => $bahan,
            'kategori_list' => config('lotra.kategori_list', []),
            'satuan_list' => config('lotra.satuan_list', []),
        ]);
    }

    /**
     * Dropdown kelompok dinamis berdasarkan kategori (JSON).
     */
    public function kelompok(Request $request)
    {
        $kategori = trim((string) $request->input('kategori', ''));

        if ($kategori === '') {
            return response()->json(['kelompok' => ['Lainnya']]);
        }

        $list = Bahan::where('kategori', $kategori)
            ->select('kelompok', DB::raw('MIN(urutan) as min_u'))
            ->groupBy('kelompok')
            ->orderBy('min_u')
            ->pluck('kelompok')
            ->all();

        if (empty($list)) {
            $list = ['Lainnya'];
        }

        return response()->json(['kelompok' => $list]);
    }

    /**
     * Simpan Barang baru (Tambah).
     */
    public function store(MasterBahanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $kode = strtolower(trim($validated['kode']));
        $urutan = intval($validated['urutan'] ?? 999);
        Bahan::create([
            'kode' => $kode,
            'nama' => trim($validated['nama']),
            'kategori' => trim($validated['kategori'] ?? 'Lainnya') ?: 'Lainnya',
            'kelompok' => trim($validated['kelompok'] ?? 'Lainnya') ?: 'Lainnya',
            'satuan' => trim($validated['satuan'] ?? 'pcs') ?: 'pcs',
            'urutan' => $urutan,
            'sort_order' => $urutan,
            'is_active' => 1,
        ]);

        $this->addBahanColumn($kode);

        flash_success('Bahan berhasil ditambahkan.');

        return redirect()->route('manager.master-bahan');
    }

    /**
     * Perbarui Barang (Edit).
     */
    public function update(MasterBahanRequest $request, int $id): RedirectResponse
    {
        $bahan = Bahan::findOrFail($id);
        $validated = $request->validated();

        $kode = strtolower(trim($validated['kode']));
        $oldKode = $bahan->kode;

        $urutan = intval($validated['urutan'] ?? 999);
        $bahan->update([
            'kode' => $kode,
            'nama' => trim($validated['nama']),
            'kategori' => trim($validated['kategori'] ?? 'Lainnya') ?: 'Lainnya',
            'kelompok' => trim($validated['kelompok'] ?? 'Lainnya') ?: 'Lainnya',
            'satuan' => trim($validated['satuan'] ?? 'pcs') ?: 'pcs',
            'urutan' => $urutan,
            'sort_order' => $urutan,
        ]);

        if ($kode !== $oldKode) {
            $this->renameBahanColumn($oldKode, $kode);
        }

        flash_success('Bahan berhasil diperbarui.');

        return redirect()->route('manager.master-bahan');
    }

    /**
     * Hapus Barang.
     */
    public function destroy(int $id): RedirectResponse
    {
        $bahan = Bahan::findOrFail($id);
        $kode = $bahan->kode;

        $bahan->delete();
        $this->dropBahanColumn($kode);

        flash_success('Bahan berhasil dihapus.');

        return redirect()->route('manager.master-bahan');
    }

    /**
     * Toggle status aktif/nonaktif Barang.
     */
    public function toggle(int $id): RedirectResponse
    {
        $bahan = Bahan::findOrFail($id);
        $bahan->update(['is_active' => $bahan->is_active ? 0 : 1]);

        flash_success('Status bahan berhasil diubah.');

        return redirect()->route('manager.master-bahan');
    }

    // =========================================================
    // SINKRONISASI KOLOM TABEL TRANSAKSI
    // =========================================================

    private function isSafeColumn(string $kode): bool
    {
        return (bool) preg_match('/^[a-z0-9_]+$/', $kode);
    }

    private function addBahanColumn(string $kode): void
    {
        if (! $this->isSafeColumn($kode)) {
            return;
        }

        foreach ($this->transactionTables as $table) {
            if (! Schema::hasColumn($table, $kode)) {
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN `{$kode}` VARCHAR(12) NULL");
            }
        }
    }

    private function renameBahanColumn(string $oldKode, string $newKode): void
    {
        if (! $this->isSafeColumn($newKode)) {
            return;
        }

        foreach ($this->transactionTables as $table) {
            if (Schema::hasColumn($table, $oldKode)) {
                DB::statement("ALTER TABLE `{$table}` CHANGE `{$oldKode}` `{$newKode}` VARCHAR(12) NULL");
            }
        }
    }

    private function dropBahanColumn(string $kode): void
    {
        if (! $this->isSafeColumn($kode)) {
            return;
        }

        foreach ($this->transactionTables as $table) {
            if (Schema::hasColumn($table, $kode)) {
                DB::statement("ALTER TABLE `{$table}` DROP COLUMN `{$kode}`");
            }
        }
    }
}
