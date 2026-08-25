<?php

namespace App\Services;

use App\Models\Bahan;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export menggunakan PhpSpreadsheet (.xlsx) dan Dompdf (.pdf).
 *
 * Layout & style 100% meniru project Flask (openpyxl & ReportLab):
 *   - Excel: 3-level header (Kategori→Kelompok→Barang) warna coklat
 *   - PDF:   logo + header coklat + tabel striped + footer
 *
 * Tidak mengubah logika bisnis, query database, filter, atau data.
 */
class ExportService
{
    // =========================================================
    // KONSTANTA STYLE (homolog modules/excel_handler.py Flask)
    // =========================================================
    private const CAT_FILL    = '4A3428';  // coklat tua
    private const GROUP_FILL  = '6B4F3A';  // coklat sedang
    private const ITEM_FILL   = '8D6E63';  // coklat muda
    private const BORDER_COLOR = 'D1D5DB'; // abu-abu tipis

    private const ROW_CAT   = 1;   // Baris Kategori
    private const ROW_GROUP = 2;   // Baris Kelompok
    private const ROW_ITEM  = 3;   // Baris Nama Barang
    private const ROW_DATA  = 4;   // Baris data mulai

    // =========================================================
    // COLOR HELPERS (semua kategori menggunakan warna coklat default)
    // =========================================================
    /**
     * Bangun peta kolom -> label kategori (untuk separator border antar kategori).
     */
    private static function buildColCatMap(array $catCols, int $nCols): array
    {
        $map = [];
        foreach ($catCols as [$start, $end, $label]) {
            for ($c = $start; $c <= $end; $c++) {
                $map[$c] = $label;
            }
        }
        for ($c = 1; $c <= $nCols; $c++) {
            if (! isset($map[$c])) {
                $map[$c] = 'LAINNYA';
            }
        }
        return $map;
    }

    // =========================================================
    // STYLE HELPERS (homolog apply_*_style Flask)
    // =========================================================
    private static function applyCategoryStyle(Worksheet $ws, int $col, int $row, ?string $hexColor = null): void
    {
        $color = $hexColor ?? self::CAT_FILL;
        $coord = Coordinate::stringFromColumnIndex($col) . $row;
        $ws->getStyle($coord)
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color($color));
        $ws->getStyle($coord)->getFont()
            ->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'))->setSize(12);
        $ws->getStyle($coord)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        self::setBorder($ws, $col, $row);
    }

    private static function applyGroupStyle(Worksheet $ws, int $col, int $row): void
    {
        $coord = Coordinate::stringFromColumnIndex($col) . $row;
        $ws->getStyle($coord)
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color(self::GROUP_FILL));
        $ws->getStyle($coord)->getFont()
            ->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'))->setSize(11);
        $ws->getStyle($coord)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        self::setBorder($ws, $col, $row);
    }

    private static function applyItemStyle(Worksheet $ws, int $col, int $row, ?string $hexColor = null): void
    {
        $color = $hexColor ?? self::ITEM_FILL;
        $coord = Coordinate::stringFromColumnIndex($col) . $row;
        $ws->getStyle($coord)
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color($color));
        $ws->getStyle($coord)->getFont()
            ->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'))->setSize(11);
        $ws->getStyle($coord)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        self::setBorder($ws, $col, $row);
    }

    private static function applyDataStyle(Worksheet $ws, int $col, int $row, bool $leftAlign = false): void
    {
        $coord = Coordinate::stringFromColumnIndex($col) . $row;
        $ws->getStyle($coord)
            ->getFill()->setFillType(Fill::FILL_NONE);
        $ws->getStyle($coord)->getFont()
            ->setBold(false)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('000000'))->setSize(11);
        $ws->getStyle($coord)->getAlignment()
            ->setHorizontal($leftAlign ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        self::setBorder($ws, $col, $row);
    }

    private static function setBorder(Worksheet $ws, int $col, int $row): void
    {
        $thin = ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::BORDER_COLOR]];
        $coord = Coordinate::stringFromColumnIndex($col) . $row;
        $ws->getStyle($coord)->applyFromArray([
            'borders' => [
                'top'    => $thin,
                'right'  => $thin,
                'bottom' => $thin,
                'left'   => $thin,
            ],
        ]);
    }

    // =========================================================
    // BUILD WIDE HEADER (homolog build_wide_header_spec Flask)
    // =========================================================
    private static function buildWideHeaderSpec(array $baseLabels, array $items): array
    {
        $nBase = count($baseLabels);
        $n = count($items);
        $itemLabels = array_merge($baseLabels, array_column($items, 'nama'));
        $nCols = count($itemLabels);

        // Baris 1: Kategori
        $catCols = [[1, $nBase, 'INFORMASI STOK']];
        $j = 0;
        while ($j < $n) {
            $kat = $items[$j]['kategori'];
            $start = $nBase + 1 + $j;
            while ($j < $n && ($items[$j]['kategori'] ?? 'Lainnya') === $kat) {
                $j++;
            }
            $catCols[] = [$start, $nBase + $j, strtoupper($kat)];
        }

        // Baris 2: Kelompok
        $groupCols = [];
        foreach ($baseLabels as $i => $lab) {
            $groupCols[] = [$i + 1, $i + 1, $lab];
        }
        $j = 0;
        while ($j < $n) {
            $kel = $items[$j]['kelompok'];
            $start = $nBase + 1 + $j;
            while ($j < $n && ($items[$j]['kelompok'] ?? 'Lainnya') === $kel) {
                $j++;
            }
            $groupCols[] = [$start, $nBase + $j, strtoupper($kel)];
        }

        return [$catCols, $groupCols, $itemLabels];
    }

    /**
     * Tentukan apakah ini WIDE layout (memiliki base labels + barang)
     * dengan mengecek apakah catCol pertama mencakup seluruh kolom.
     */
    private static function isWideLayout(array $catCols, int $nCols): bool
    {
        // FLAT: catCols[0] = [1, nCols, ...]; WIDE: catCols[0] = [1, nBase, ...]
        if (empty($catCols)) {
            return false;
        }
        [$start, $end] = $catCols[0];
        return ($end - $start + 1) < $nCols;
    }

    private static function applyThreeLevelLayout(Worksheet $ws, int $nCols, array $catCols, array $groupCols, array $itemLabels, array $rows, array $leftCols = [], array $minWidths = []): void
    {
        $isWide = self::isWideLayout($catCols, $nCols);
        $colCatMap = self::buildColCatMap($catCols, $nCols);

        // Hitung batas kategori (kolom terakhir setiap kategori)
        $catEndCols = [];
        foreach ($catCols as [$start, $end, $label]) {
            $catEndCols[$end] = $label;
        }

        // === Row 1: Kategori ===
        foreach ($catCols as [$start, $end, $label]) {
            if ($start < $end) {
                $ws->mergeCells(
                    Coordinate::stringFromColumnIndex($start) . self::ROW_CAT . ':' .
                    Coordinate::stringFromColumnIndex($end) . self::ROW_CAT
                );
            }
            $cell = $ws->getCell(Coordinate::stringFromColumnIndex($start) . self::ROW_CAT);
            $cell->setValue($label);
            for ($c = $start; $c <= $end; $c++) {
                self::applyCategoryStyle($ws, $c, self::ROW_CAT);
            }
        }

        // === Row 2: Kelompok (Group) ===
        foreach ($groupCols as [$start, $end, $label]) {
            if ($start < $end) {
                $ws->mergeCells(
                    Coordinate::stringFromColumnIndex($start) . self::ROW_GROUP . ':' .
                    Coordinate::stringFromColumnIndex($end) . self::ROW_GROUP
                );
            }
            $cell = $ws->getCell(Coordinate::stringFromColumnIndex($start) . self::ROW_GROUP);
            $cell->setValue($label);
            for ($c = $start; $c <= $end; $c++) {
                self::applyGroupStyle($ws, $c, self::ROW_GROUP);
            }
        }

        // === Row 3: Nama Barang / Sub-header ===
        // Untuk WIDE layout: 3 kolom pertama kosong (tidak duplicate)
        $nBase = $isWide ? 3 : 0; // Tanggal, Shift, Barista
        foreach ($itemLabels as $i => $label) {
            $col = $i + 1;
            // Di WIDE layout, base labels (Tanggal/Shift/Barista) sudah ada di Row 2,
            // jadi Row 3 untuk 3 kolom pertama dikosongkan
            $cell = $ws->getCell(Coordinate::stringFromColumnIndex($col) . self::ROW_ITEM);
            if ($isWide && $col <= $nBase) {
                $cell->setValue('');
            } else {
                $cell->setValue($label);
            }
            // Gunakan warna item default (coklat) untuk semua kategori
            self::applyItemStyle($ws, $col, self::ROW_ITEM);
        }

        // === Row 4+: Data ===
        foreach ($rows as $rIdx => $row) {
            $rowNum = self::ROW_DATA + $rIdx;
            foreach ($row as $cIdx => $val) {
                $col = $cIdx + 1;
                $ws->getCell(Coordinate::stringFromColumnIndex($col) . $rowNum)->setValue($val);
                $left = in_array($col, $leftCols);
                self::applyDataStyle($ws, $col, $rowNum, $left);
            }
        }

        // === Thicker right border antar kategori (REVISI 5) ===
        $mediumBorder = ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => '9CA3AF']];
        foreach ($catEndCols as $endCol => $catLabel) {
            if ($endCol < $nCols) {
                // Row 1 (Kategori)
                $r1coord = Coordinate::stringFromColumnIndex($endCol) . self::ROW_CAT;
                $ws->getStyle($r1coord)->applyFromArray(['borders' => ['right' => $mediumBorder]]);
                // Row 2 (Kelompok)
                $r2coord = Coordinate::stringFromColumnIndex($endCol) . self::ROW_GROUP;
                $ws->getStyle($r2coord)->applyFromArray(['borders' => ['right' => $mediumBorder]]);
                // Row 3 (Item)
                $r3coord = Coordinate::stringFromColumnIndex($endCol) . self::ROW_ITEM;
                $ws->getStyle($r3coord)->applyFromArray(['borders' => ['right' => $mediumBorder]]);
            }
        }

        // === Set row heights ===
        $ws->getRowDimension(self::ROW_CAT)->setRowHeight(24);
        $ws->getRowDimension(self::ROW_GROUP)->setRowHeight(22);
        $ws->getRowDimension(self::ROW_ITEM)->setRowHeight(22);

        // === Auto-width ===
        if (! empty($minWidths)) {
            for ($c = 1; $c <= $nCols; $c++) {
                $maxLen = 0;
                for ($r = self::ROW_CAT; $r <= self::ROW_DATA + count($rows) - 1; $r++) {
                    $v = $ws->getCell(Coordinate::stringFromColumnIndex($c) . $r)->getValue();
                    if ($v !== null) {
                        $len = mb_strlen((string) $v);
                        if ($len > $maxLen) {
                            $maxLen = $len;
                        }
                    }
                }
                $idx = $c - 1;
                $minW = isset($minWidths[$idx]) ? (int) $minWidths[$idx] : 10;
                $width = max($minW, min($maxLen + 2, 40));
                $ws->getColumnDimensionByColumn($c)->setWidth($width);
            }
        } else {
            for ($c = 1; $c <= $nCols; $c++) {
                $maxLen = 0;
                for ($r = self::ROW_CAT; $r <= self::ROW_DATA + count($rows) - 1; $r++) {
                    $v = $ws->getCell(Coordinate::stringFromColumnIndex($c) . $r)->getValue();
                    if ($v !== null) {
                        $len = mb_strlen((string) $v);
                        if ($len > $maxLen) {
                            $maxLen = $len;
                        }
                    }
                }
                $width = max(10, min($maxLen + 2, 40));
                if ($c <= 3) {
                    $width = $c === 1 ? 16 : ($c === 2 ? 14 : 28); // Tanggal=16, Shift=14, Barista=28
                } elseif ($maxLen > 0) {
                    $width = max(12, $width);
                }
                $ws->getColumnDimensionByColumn($c)->setWidth($width);
            }
        }
    }

    private static function toFloat($val): ?float
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

    private static function formatNumber($v): string
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

    /**
     * Urut items persis seperti Flask: kategori ASC, urutan ASC, id ASC.
     * Homolog master_bahan.get_active() di Flask.
     */
    private static function sortItems(array $items): array
    {
        usort($items, function ($a, $b) {
            $katCmp = strcmp($a['kategori'] ?? '', $b['kategori'] ?? '');
            if ($katCmp !== 0) {
                return $katCmp;
            }
            $sortCmp = ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
            if ($sortCmp !== 0) {
                return $sortCmp;
            }
            return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
        });
        return $items;
    }

    /**
     * Format Carbon/DateTime jadi string Y-m-d, dengan fallback '-' bila kosong.
     * Homolog StokMasukRiwayat._normalize_date di Flask.
     */
    private static function formatDate($date): string
    {
        if ($date === null || $date === '') {
            return '-';
        }
        if ($date instanceof \Carbon\Carbon || $date instanceof \DateTime) {
            return $date->format('Y-m-d');
        }
        return (string) $date;
    }

    // =========================================================
    // XLSX RESPONSE
    // =========================================================
    private static function xlsxResponse(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $filename = str_replace('"', '', $filename);
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    // =========================================================
    // PDF RESPONSE (via Dompdf)
    // =========================================================
    private static function pdfResponse(string $html, string $filename): StreamedResponse
    {
        $filename = str_replace('"', '', $filename);
        return response()->streamDownload(function () use ($html) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html);
            $dompdf->render();
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    // =========================================================
    // 1. STOK MASUK EXCEL (WIDE)
    // =========================================================
    public static function stokMasukExcel(Collection $raw, string $periode, string $dicetak): StreamedResponse
    {
        $items = Bahan::activeItems();
        // Urut items seperti Flask: kategori ASC, sort_order ASC, id ASC
        $items = self::sortItems($items);

        $baseLabels = ['Tanggal', 'Shift', 'Barista'];
        $itemKeys = array_column($items, 'kode');
        [$catCols, $groupCols, $itemLabels] = self::buildWideHeaderSpec($baseLabels, $items);
        $nCols = count($itemLabels);

        $dataRows = [];
        foreach ($raw as $rec) {
            // Format tanggal jadi string Y-m-d, default '-' bila kosong (homolog Flask)
            $tanggalStr = self::formatDate($rec->tanggal);
            $shiftStr = $rec->shift ?: '-';
            $baristaStr = $rec->barista ?: '-';
            $row = [$tanggalStr, $shiftStr, $baristaStr];
            foreach ($itemKeys as $key) {
                $rawVal = $rec->$key;
                if ($rawVal === null || $rawVal === '') {
                    $row[] = 0;
                } else {
                    $fv = self::toFloat($rawVal);
                    $row[] = ($fv !== null && $fv == (int) $fv) ? (int) $fv : $rawVal;
                }
            }
            $dataRows[] = $row;
        }

        $spreadsheet = new Spreadsheet();
        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle('Riwayat Stok Masuk');
        self::applyThreeLevelLayout($ws, $nCols, $catCols, $groupCols, $itemLabels, $dataRows, [3]);

        return self::xlsxResponse($spreadsheet, 'riwayat_stok_masuk_'.now()->format('Ymd_His').'.xlsx');
    }

    // =========================================================
    // 2. UPDATE STOK EXCEL (WIDE)
    // =========================================================
    public static function updateStokExcel(Collection $raw, string $filterInfo, string $dicetak): StreamedResponse
    {
        $items = Bahan::activeItems();
        $items = self::sortItems($items);

        $baseLabels = ['Tanggal', 'Shift', 'Barista'];
        $itemKeys = array_column($items, 'kode');
        [$catCols, $groupCols, $itemLabels] = self::buildWideHeaderSpec($baseLabels, $items);
        $nCols = count($itemLabels);

        $dataRows = [];
        foreach ($raw as $rec) {
            // Format tanggal jadi string Y-m-d, default '-' bila kosong (homolog Flask)
            $tanggalStr = self::formatDate($rec->tanggal);
            $shiftStr = $rec->shift ?: '-';
            $baristaStr = $rec->barista ?: '-';
            $row = [$tanggalStr, $shiftStr, $baristaStr];
            foreach ($itemKeys as $key) {
                $rawVal = $rec->$key;
                if ($rawVal === null || $rawVal === '') {
                    $row[] = 0;
                } else {
                    $fv = self::toFloat($rawVal);
                    $row[] = ($fv !== null && $fv == (int) $fv) ? (int) $fv : $rawVal;
                }
            }
            $dataRows[] = $row;
        }

        $spreadsheet = new Spreadsheet();
        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle('Riwayat Update');
        self::applyThreeLevelLayout($ws, $nCols, $catCols, $groupCols, $itemLabels, $dataRows, [3]);

        return self::xlsxResponse($spreadsheet, 'riwayat_update_stok_'.now()->format('Ymd_His').'.xlsx');
    }

    // =========================================================
    // 3. UPDATE STOK PDF
    // =========================================================
    public static function updateStokPdf(array $records, string $filterInfo, string $dicetak): StreamedResponse
    {
        $periodeTxt = 'Seluruh Riwayat';
        if ($filterInfo) {
            $periodeTxt .= ' | '.$filterInfo;
        }

        $html = self::pdfHeader('Riwayat Update Stok', $periodeTxt);

        if (empty($records)) {
            $html .= '<p style="font-family:Helvetica,sans-serif;font-size:10pt;color:#555;">Tidak ada data riwayat yang sesuai dengan filter.</p>';
        } else {
            $labelToGroup = self::getLabelToGroup();
            $fullW = 530; // A4 width minus margins

            foreach ($records as $i => $rec) {
                // Header record: No | Tanggal | Shift | Barista | Jumlah Item
                $html .= '<table class="record-header" style="width:100%;border-collapse:collapse;margin-top:8px;margin-bottom:4px;">';
                $html .= '<thead><tr style="background-color:#6F4E37;color:white;font-family:Helvetica,sans-serif;font-size:9pt;">';
                $html .= '<th style="width:40px;padding:3px 6px;border:0.5px solid #D7CCC8;text-align:left;">No</th>';
                $html .= '<th style="width:100px;padding:3px 6px;border:0.5px solid #D7CCC8;text-align:left;">Tanggal</th>';
                $html .= '<th style="width:90px;padding:3px 6px;border:0.5px solid #D7CCC8;text-align:left;">Shift</th>';
                $html .= '<th style="padding:3px 6px;border:0.5px solid #D7CCC8;text-align:left;">Barista</th>';
                $html .= '<th style="width:80px;padding:3px 6px;border:0.5px solid #D7CCC8;text-align:left;">Jumlah Item</th>';
                $html .= '</tr></thead><tbody>';
                $html .= '<tr style="font-family:Helvetica,sans-serif;font-size:9pt;">';
                $html .= '<td style="padding:3px 6px;border:0.5px solid #D7CCC8;font-weight:bold;">'.($i + 1).'</td>';
                $html .= '<td style="padding:3px 6px;border:0.5px solid #D7CCC8;">'.htmlspecialchars($rec['tanggal_display']).'</td>';
                $html .= '<td style="padding:3px 6px;border:0.5px solid #D7CCC8;">'.htmlspecialchars($rec['shift']).'</td>';
                $html .= '<td style="padding:3px 6px;border:0.5px solid #D7CCC8;">'.htmlspecialchars($rec['barista']).'</td>';
                $html .= '<td style="padding:3px 6px;border:0.5px solid #D7CCC8;">'.$rec['jumlah_item'].'</td>';
                $html .= '</tr></tbody></table>';

                // Detail barang dikelompokkan Kategori -> Kelompok -> Barang
                $items = $rec['items'] ?? [];
                if (! empty($items)) {
                    $tree = [];
                    foreach ($items as $it) {
                        $lab = $it['label'] ?? '-';
                        [$kat, $kel] = $labelToGroup[$lab] ?? ['Lainnya', 'Lainnya'];
                        $tree[$kat][$kel][] = $it;
                    }

                    // Urut kategori
                    $orderKat = ['Bahan Baku Bar', 'Bahan Baku Kitchen', 'Equipment'];
                    $sortedTree = [];
                    foreach ($orderKat as $kat) {
                        if (isset($tree[$kat])) {
                            $sortedTree[$kat] = $tree[$kat];
                            unset($tree[$kat]);
                        }
                    }
                    foreach ($tree as $kat => $kelDict) {
                        $sortedTree[$kat] = $kelDict;
                    }

                    foreach ($sortedTree as $kat => $kelDict) {
                        $html .= '<div style="font-family:Helvetica,sans-serif;font-size:10pt;font-weight:bold;color:#3E2723;margin:6px 0 2px 4px;">'.htmlspecialchars($kat).'</div>';
                        foreach ($kelDict as $kel => $its) {
                            $html .= '<div style="font-family:Helvetica,sans-serif;font-size:9pt;font-weight:bold;color:#6F4E37;margin:3px 0 2px 12px;">'.htmlspecialchars($kel).'</div>';
                            $html .= '<table style="width:100%;border-collapse:collapse;margin:2px 0 6px 12px;">';
                            $html .= '<thead><tr style="background-color:#6F4E37;color:white;font-family:Helvetica,sans-serif;font-size:8pt;">';
                            $html .= '<th style="width:30px;padding:3px 6px;border:0.5px solid #D7CCC8;text-align:left;">No</th>';
                            $html .= '<th style="padding:3px 6px;border:0.5px solid #D7CCC8;text-align:left;">Nama Barang</th>';
                            $html .= '<th style="width:80px;padding:3px 6px;border:0.5px solid #D7CCC8;text-align:left;">Stok</th>';
                            $html .= '<th style="width:80px;padding:3px 6px;border:0.5px solid #D7CCC8;text-align:left;">Status</th>';
                            $html .= '</tr></thead><tbody>';
                            foreach ($its as $j => $it) {
                                $bg = ($j % 2 === 0) ? 'white' : '#F5F0EB';
                                $html .= '<tr style="background-color:'.$bg.';font-family:Helvetica,sans-serif;font-size:8pt;">';
                                $html .= '<td style="padding:3px 6px;border:0.5px solid #D7CCC8;">'.($j + 1).'</td>';
                                $html .= '<td style="padding:3px 6px;border:0.5px solid #D7CCC8;">'.htmlspecialchars($it['label'] ?? '-').'</td>';
                                $html .= '<td style="padding:3px 6px;border:0.5px solid #D7CCC8;">'.htmlspecialchars($it['value'] ?? '-').'</td>';
                                $html .= '<td style="padding:3px 6px;border:0.5px solid #D7CCC8;">'.htmlspecialchars(ucfirst($it['status'] ?? '-')).'</td>';
                                $html .= '</tr>';
                            }
                            $html .= '</tbody></table>';
                        }
                    }
                }
            }
        }

        $html .= self::pdfFooter($dicetak);

        return self::pdfResponse($html, 'riwayat_update_stok_'.now()->format('Ymd_His').'.pdf');
    }

    // =========================================================
    // 4. FORECAST EXCEL (FLAT)
    // =========================================================
    public static function forecastExcel(array $data, string $periode, string $dicetak): StreamedResponse
    {
        $items = $data['items'] ?? [];

        $spreadsheet = new Spreadsheet();
        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle('Forecast');

        $itemLabels = ['No', 'Nama Barang', 'Stok Sekarang', 'Forecast Kebutuhan', 'Estimasi Pembelian', 'Status'];
        $nCols = count($itemLabels);

        // Flat layout: Row 1 = INFORMASI STOK (merge), Row 2 = FORECAST (merge), Row 3 = headers, Row 4 = data
        $catCols = [[1, $nCols, 'INFORMASI STOK']];
        $groupCols = [[1, $nCols, 'FORECAST']];

        $metricLabels = ['aman' => 'Aman', 'perlu_dibeli' => 'Perlu Dibeli', 'habis' => 'Habis'];

        $dataRows = [];
        foreach ($items as $idx => $it) {
            $status = $it['status'] ?? '';
            $status = $metricLabels[$status] ?? $status;
            $dataRows[] = [
                $idx + 1,
                $it['nama_barang'] ?? '',
                $it['stok_sekarang'] ?? '',
                $it['kebutuhan'] ?? '',
                $it['estimasi_pembelian'] ?? '',
                $status,
            ];
        }

        $forecastMinWidths = [6, 25, 15, 18, 18, 15]; // homolog Flask: [No=6, Nama=25, Stok=15, Kebutuhan=18, Estimasi=18, Status=15]
        self::applyThreeLevelLayout($ws, $nCols, $catCols, $groupCols, $itemLabels, $dataRows, [2], $forecastMinWidths);

        return self::xlsxResponse($spreadsheet, 'forecast_'.now()->format('Ymd_His').'.xlsx');
    }

    // =========================================================
    // 5. FORECAST PDF
    // =========================================================
    public static function forecastPdf(array $data, string $dicetak): StreamedResponse
    {
        $periode = '';
        if (($data['tanggal_awal'] ?? '') && ($data['tanggal_akhir'] ?? '')) {
            $periode = $data['tanggal_awal'].' s.d. '.$data['tanggal_akhir'];
        }

        $html = self::pdfHeader('Forecast Mingguan', $periode ?: '-');
        $html .= '<p style="font-family:Helvetica,sans-serif;font-size:10pt;">'
            .'Total Kebutuhan: '.($data['total_kebutuhan'] ?? 0)
            .' &nbsp;&nbsp; Total Estimasi Pembelian: '.($data['total_estimasi_pembelian'] ?? 0)
            .'</p>';

        $fullW = 530;
        foreach ($data['items_tree'] ?? [] as $node) {
            $html .= '<div style="font-family:Helvetica,sans-serif;font-size:10pt;font-weight:bold;color:#3E2723;margin:8px 0 2px 4px;">'.htmlspecialchars($node['kategori']).'</div>';
            foreach ($node['kelompok_list'] as $grp) {
                $html .= '<div style="font-family:Helvetica,sans-serif;font-size:9pt;font-weight:bold;color:#6F4E37;margin:4px 0 2px 12px;">'.htmlspecialchars($grp['kelompok']).'</div>';
                $rows = [['No', 'Nama Barang', 'Stok', 'Kebutuhan', 'Estimasi', 'Status']];
                foreach ($grp['items'] as $j => $it) {
                    $rows[] = [$j + 1, $it['nama_barang'], $it['stok_sekarang'], $it['kebutuhan'], $it['estimasi_pembelian'], $it['status']];
                }
                $html .= self::pdfTable($rows, [30, $fullW - 230, 50, 50, 50, 50]);
            }
        }

        $html .= self::pdfFooter($dicetak);
        return self::pdfResponse($html, 'forecast_'.now()->format('Ymd_His').'.pdf');
    }

    // =========================================================
    // 6. LAPORAN PDF
    // =========================================================
    public static function laporanPdf(?string $tglAwal, ?string $tglAkhir, string $dicetak): StreamedResponse
    {
        $summary = null;
        if ($tglAwal && $tglAkhir) {
            $data = StockAnalytics::readUpdateStok();
            $limitMap = StockAnalytics::limitMap();
            $keyToLabel = StockAnalytics::keyToLabel();

            // Filter rows dalam rentang tanggal (homogen dengan ManagerController::laporan)
            $periodeRows = array_filter($data['rows'], function ($r) use ($tglAwal, $tglAkhir) {
                return $tglAwal <= $r['tanggal'] && $r['tanggal'] <= $tglAkhir;
            });
            $totalUpdateStok = count($periodeRows);

            $last = $data['last_row'];
            $aman = $tipis = $habis = 0;
            if ($last) {
                foreach ($data['item_keys'] as $key) {
                    [$lh, $lt] = $limitMap[$key] ?? [StockAnalytics::DEFAULT_LIMIT_HABIS, StockAnalytics::DEFAULT_LIMIT_TIPIS];
                    $v = StockAnalytics::toFloat($last['values'][$key] ?? null);
                    if ($v === null || $v <= $lh) {
                        $habis++;
                    } elseif ($v <= $lt) {
                        $tipis++;
                    } else {
                        $aman++;
                    }
                }
            }
            $forecast = StockAnalytics::forecast($tglAwal, $tglAkhir, $data, $limitMap);

            // Gunakan format tanggal Bahasa Indonesia (homolog Flask)
            $periodeLabel = format_tanggal_id($tglAwal).' - '.format_tanggal_id($tglAkhir);

            $summary = [
                'periode_label' => $periodeLabel,
                'total_update_stok' => $totalUpdateStok,
                'barang_aman' => $aman,
                'barang_tipis' => $tipis,
                'barang_habis' => $habis,
                'has_data' => $data['has_data'],
                'top_barang_habis' => StockAnalytics::topHabis($data, $limitMap, $keyToLabel),
                'top_barang_tipis' => StockAnalytics::topTipis($data, $limitMap, $keyToLabel),
                'aktivitas_barista' => StockAnalytics::aktivitasBarista($data),
                'total_kebutuhan' => $forecast['total_kebutuhan'],
                'total_estimasi_pembelian' => $forecast['total_estimasi_pembelian'],
                'forecast_items' => $forecast['items'],
                'forecast_items_tree' => $forecast['items_tree'],
            ];
        }

        $html = self::pdfHeader('Laporan Mingguan Inventory', $summary ? $summary['periode_label'] : 'Belum ada filter');

        if (! $summary) {
            $html .= '<p style="font-family:Helvetica,sans-serif;font-size:10pt;color:#555;">Pilih rentang tanggal terlebih dahulu.</p>';
            return self::pdfResponse($html, 'laporan_'.now()->format('Ymd_His').'.pdf');
        }

        // Ringkasan Statistik
        $html .= '<div style="font-family:Helvetica,sans-serif;font-size:10pt;font-weight:bold;color:#6F4E37;margin:8px 0 4px;padding:4px 6px;background-color:#6F4E37;color:white;">Ringkasan Statistik</div>';
        $html .= self::pdfTable([
            ['Total Update Stok', 'Barang Aman', 'Barang Tipis', 'Barang Habis'],
            [$summary['total_update_stok'], $summary['barang_aman'], $summary['barang_tipis'], $summary['barang_habis']],
        ], [130, 130, 130, 130]);

        $fullW = 530;

        // Top Barang Habis
        $html .= '<div style="font-family:Helvetica,sans-serif;font-size:10pt;font-weight:bold;color:#6F4E37;margin:8px 0 4px;padding:4px 6px;background-color:#6F4E37;color:white;">Top Barang Paling Sering Habis</div>';
        if (! empty($summary['top_barang_habis'])) {
            $rows = [['Rank', 'Nama Barang', 'Jumlah Habis']];
            foreach ($summary['top_barang_habis'] as $it) {
                $rows[] = [$it['rank'], $it['nama_barang'], $it['jumlah']];
            }
            $html .= self::pdfTable($rows, [60, $fullW - 180, 120]);
        }

        // Top Barang Tipis / Hampir Habis
        $html .= '<div style="font-family:Helvetica,sans-serif;font-size:10pt;font-weight:bold;color:#6F4E37;margin:8px 0 4px;padding:4px 6px;background-color:#6F4E37;color:white;">Top Barang Hampir Habis</div>';
        if (! empty($summary['top_barang_tipis'])) {
            $rows = [['Rank', 'Nama Barang', 'Jumlah Tipis']];
            foreach ($summary['top_barang_tipis'] as $it) {
                $rows[] = [$it['rank'], $it['nama_barang'], $it['jumlah']];
            }
            $html .= self::pdfTable($rows, [60, $fullW - 180, 120]);
        }

        // Aktivitas Barista
        if (! empty($summary['aktivitas_barista'])) {
            $html .= '<div style="font-family:Helvetica,sans-serif;font-size:10pt;font-weight:bold;color:#6F4E37;margin:8px 0 4px;padding:4px 6px;background-color:#6F4E37;color:white;">Aktivitas Barista</div>';
            $rows = [['No', 'Nama Barista', 'Jumlah Update Stok']];
            foreach ($summary['aktivitas_barista'] as $it) {
                $rows[] = [$it['no'], $it['nama_barista'], $it['jumlah']];
            }
            $html .= self::pdfTable($rows, [40, $fullW - 180, 140]);
        }

        // Forecast Mingguan & Estimasi Pembelian
        $html .= '<div style="font-family:Helvetica,sans-serif;font-size:10pt;font-weight:bold;color:#6F4E37;margin:8px 0 4px;padding:4px 6px;background-color:#6F4E37;color:white;">Forecast Mingguan &amp; Estimasi Pembelian</div>';
        if (! empty($summary['forecast_items'])) {
            $html .= '<table class="table-total" style="margin-bottom:6px;"><tr><th style="width:50%;">Total Kebutuhan</th><th style="width:50%;">Total Estimasi Pembelian</th></tr><tr><td>' . htmlspecialchars((string) $summary['total_kebutuhan']) . '</td><td>' . htmlspecialchars((string) $summary['total_estimasi_pembelian']) . '</td></tr></table>';

            // Render ber-struktur Kategori -> Kelompok -> Barang
            foreach ($summary['forecast_items_tree'] as $node) {
                $html .= '<div class="cat-title">' . htmlspecialchars($node['kategori']) . '</div>';
                foreach ($node['kelompok_list'] as $grp) {
                    $html .= '<div class="grp-title">' . htmlspecialchars($grp['kelompok']) . '</div>';
                    $rows = [['No', 'Nama Barang', 'Stok Sekarang', 'Forecast Kebutuhan', 'Estimasi Pembelian']];
                    foreach ($grp['items'] as $j => $it) {
                        $rows[] = [$j + 1, $it['nama_barang'], $it['stok_sekarang'], $it['kebutuhan'], $it['estimasi_pembelian']];
                    }
                    $html .= self::pdfTable($rows, [30, $fullW - 230, 50, 65, 65]);
                }
            }
        } else {
            $html .= '<p style="font-family:Helvetica,sans-serif;font-size:10pt;color:#555;">Tidak ada data forecast untuk periode ini.</p>';
        }

        $html .= self::pdfFooter($dicetak);
        return self::pdfResponse($html, 'laporan_'.now()->format('Ymd_His').'.pdf');
    }

    public static function laporanExcel(?string $tglAwal, ?string $tglAkhir, string $dicetak): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle('Laporan Mingguan');

        $periode = ($tglAwal && $tglAkhir) ? format_tanggal_id($tglAwal) . ' - ' . format_tanggal_id($tglAkhir) : 'Belum ada filter';

        $ws->setCellValue('A1', 'Laporan Mingguan Inventory');
        $ws->setCellValue('A2', 'Periode: ' . $periode);
        $ws->setCellValue('A3', 'Dicetak oleh: ' . $dicetak . ' pada ' . now()->format('d/m/Y H:i'));

        $ws->getStyle('A1:A3')->getFont()->setBold(true);

        if ($tglAwal && $tglAkhir) {
            $data = StockAnalytics::readUpdateStok();
            $limitMap = StockAnalytics::limitMap();
            $keyToLabel = StockAnalytics::keyToLabel();

            $periodeRows = array_filter($data['rows'], function ($r) use ($tglAwal, $tglAkhir) {
                return $tglAwal <= $r['tanggal'] && $r['tanggal'] <= $tglAkhir;
            });
            $totalUpdateStok = count($periodeRows);

            $last = $data['last_row'];
            $aman = $tipis = $habis = 0;
            if ($last) {
                foreach ($data['item_keys'] as $key) {
                    [$lh, $lt] = $limitMap[$key] ?? [StockAnalytics::DEFAULT_LIMIT_HABIS, StockAnalytics::DEFAULT_LIMIT_TIPIS];
                    $v = StockAnalytics::toFloat($last['values'][$key] ?? null);
                    if ($v === null || $v <= $lh) {
                        $habis++;
                    } elseif ($v <= $lt) {
                        $tipis++;
                    } else {
                        $aman++;
                    }
                }
            }

            $ws->setCellValue('A5', 'Ringkasan Statistik');
            $ws->getStyle('A5')->getFont()->setBold(true);
            $ws->setCellValue('A6', 'Total Update Stok');
            $ws->setCellValue('B6', $totalUpdateStok);
            $ws->setCellValue('A7', 'Barang Aman');
            $ws->setCellValue('B7', $aman);
            $ws->setCellValue('A8', 'Barang Tipis');
            $ws->setCellValue('B8', $tipis);
            $ws->setCellValue('A9', 'Barang Habis');
            $ws->setCellValue('B9', $habis);

            $rowIdx = 11;
            
            // Top Habis
            $topHabis = StockAnalytics::topHabis($data, $limitMap, $keyToLabel);
            if (!empty($topHabis)) {
                $ws->setCellValue('A' . $rowIdx, 'Top Barang Paling Sering Habis');
                $ws->getStyle('A' . $rowIdx)->getFont()->setBold(true);
                $rowIdx++;
                $ws->setCellValue('A' . $rowIdx, 'Rank');
                $ws->setCellValue('B' . $rowIdx, 'Nama Barang');
                $ws->setCellValue('C' . $rowIdx, 'Jumlah');
                $ws->getStyle('A'.$rowIdx.':C'.$rowIdx)->getFont()->setBold(true);
                $rowIdx++;
                foreach ($topHabis as $it) {
                    $ws->setCellValue('A' . $rowIdx, $it['rank']);
                    $ws->setCellValue('B' . $rowIdx, $it['nama_barang']);
                    $ws->setCellValue('C' . $rowIdx, $it['jumlah']);
                    $rowIdx++;
                }
                $rowIdx++;
            }

            // Top Tipis
            $topTipis = StockAnalytics::topTipis($data, $limitMap, $keyToLabel);
            if (!empty($topTipis)) {
                $ws->setCellValue('A' . $rowIdx, 'Top Barang Hampir Habis');
                $ws->getStyle('A' . $rowIdx)->getFont()->setBold(true);
                $rowIdx++;
                $ws->setCellValue('A' . $rowIdx, 'Rank');
                $ws->setCellValue('B' . $rowIdx, 'Nama Barang');
                $ws->setCellValue('C' . $rowIdx, 'Jumlah');
                $ws->getStyle('A'.$rowIdx.':C'.$rowIdx)->getFont()->setBold(true);
                $rowIdx++;
                foreach ($topTipis as $it) {
                    $ws->setCellValue('A' . $rowIdx, $it['rank']);
                    $ws->setCellValue('B' . $rowIdx, $it['nama_barang']);
                    $ws->setCellValue('C' . $rowIdx, $it['jumlah']);
                    $rowIdx++;
                }
            }

            foreach (range('A', 'C') as $col) {
                $ws->getColumnDimension($col)->setAutoSize(true);
            }
        } else {
            $ws->setCellValue('A5', 'Pilih rentang tanggal terlebih dahulu.');
        }

        return self::xlsxResponse($spreadsheet, 'laporan_'.now()->format('Ymd_His').'.xlsx');
    }

    // =========================================================
    // 7. TOKEN LISTRIK EXCEL (FLAT)
    // =========================================================
    public static function tokenListrikExcel(Collection $records, string $dicetak): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle('Riwayat Token Listrik');

        $itemLabels = ['No', 'Tanggal', 'Shift', 'Barista', 'R17 (kWh)', 'R18 (kWh)', 'Mesin (kWh)'];
        $nCols = count($itemLabels);

        // Flat layout: Row 1 = RIWAYAT TOKEN LISTRIK (merge), Row 2 = DATA TOKEN LISTRIK (merge), Row 3 = headers, Row 4 = data
        $catCols = [[1, $nCols, 'RIWAYAT TOKEN LISTRIK']];
        $groupCols = [[1, $nCols, 'DATA TOKEN LISTRIK']];

        $dataRows = [];
        foreach ($records as $idx => $rec) {
            $tanggalStr = self::formatDate($rec->tanggal);
            $shiftStr = $rec->shift ?: '-';
            $baristaStr = $rec->barista ?: '-';
            $r17 = $rec->token_r17 !== null ? (float) $rec->token_r17 : 0;
            $r18 = $rec->token_r18 !== null ? (float) $rec->token_r18 : 0;
            $mesin = $rec->token_mesin !== null ? (float) $rec->token_mesin : 0;
            $dataRows[] = [
                $idx + 1,
                $tanggalStr,
                $shiftStr,
                $baristaStr,
                $r17,
                $r18,
                $mesin,
            ];
        }

        $tokenMinWidths = [6, 16, 14, 28, 17, 17, 17];
        self::applyThreeLevelLayout($ws, $nCols, $catCols, $groupCols, $itemLabels, $dataRows, [2, 4], $tokenMinWidths);

        return self::xlsxResponse($spreadsheet, 'riwayat_token_listrik_'.now()->format('Ymd_His').'.xlsx');
    }

    // =========================================================
    // PDF HELPERS
    // =========================================================
    private static function pdfHeader(string $title, string $periode): string
    {
        $logoPath = public_path('static/img/logo.png');
        $logoB64 = '';
        if (file_exists($logoPath)) {
            $logoB64 = base64_encode(file_get_contents($logoPath));
        }

        $html = '<html><head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                @page { margin: 15mm 12mm 20mm 12mm; }
                body { font-family: Helvetica, sans-serif; font-size: 10pt; color: #333; }
                .header { width: 100%; border-bottom: 2px solid #6F4E37; padding-bottom: 6px; margin-bottom: 10px; }
                .header table { width: 100%; }
                .header td { vertical-align: middle; }
                .header h1 { font-size: 14pt; color: #6F4E37; margin: 0; }
                .header p { font-size: 8pt; color: #888; margin: 2px 0 0; }
                table.detail { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 9pt; }
                table.detail th { background-color: #6F4E37; color: white; font-size: 9pt; font-weight: bold; padding: 4px 6px; border: 0.5px solid #D7CCC8; text-align: left; }
                table.detail td { font-size: 9pt; padding: 4px 6px; border: 0.5px solid #D7CCC8; }
                table.detail tr:nth-child(even) td { background-color: #F5F0EB; }
                table.detail tr:nth-child(odd) td { background-color: #FFFFFF; }
                .section-title { font-size: 12pt; font-weight: bold; color: #FFFFFF; background-color: #6F4E37; padding: 4px 6px; margin: 8px 0 4px 0; }
                .cat-title { font-size: 12.5pt; font-weight: bold; color: #3E2723; margin: 8px 0 2px 4px; }
                .grp-title { font-size: 10.5pt; font-weight: bold; color: #6F4E37; margin: 4px 0 2px 12px; }
                .table-total { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 9pt; }
                .table-total th { background-color: #6F4E37; color: white; font-size: 9pt; font-weight: bold; padding: 4px 6px; border: 0.5px solid #D7CCC8; text-align: center; }
                .table-total td { font-size: 9pt; padding: 4px 6px; border: 0.5px solid #D7CCC8; text-align: center; }
                .table-total tr:nth-child(even) td { background-color: #F5F0EB; }
                .table-total tr:nth-child(odd) td { background-color: #FFFFFF; }
                .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 9pt; color: #999; text-align: center; border-top: 1px solid #ddd; padding-top: 4px; }
            </style>
        </head><body>';

        $html .= '<div class="header">';
        $html .= '<table>';
        $html .= '<tr>';
        if ($logoB64) {
            $html .= '<td style="width:60px;text-align:center;"><img src="data:image/png;base64,'.$logoB64.'" style="height:40px;"></td>';
        }
        $html .= '<td>';
        $html .= '<h1 style="text-align:center;">'.htmlspecialchars($title).'</h1>';
        $html .= '<p style="text-align:center;">'.htmlspecialchars($periode).'</p>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    private static function pdfFooter(string $managerName): string
    {
        $nowStr = now()->format('d F Y H:i');
        return '<div class="footer" style="text-align:center;font-size:9pt;color:#999;border-top:1px solid #ddd;padding-top:4px;">
            Tanggal Generate: '.$nowStr.' &nbsp;&nbsp; Nama Manager: '.htmlspecialchars($managerName).'
        </div>
        </body></html>';
    }

    private static function pdfTable(array $rows, array $widths): string
    {
        if (empty($rows)) {
            return '';
        }

        $html = '<table class="detail">';
        foreach ($rows as $i => $row) {
            $html .= '<tr>';
            foreach ($row as $j => $cell) {
                $style = '';
                if (isset($widths[$j])) {
                    $style = ' style="width:'.$widths[$j].'px;"';
                }
                if ($i === 0) {
                    $html .= '<th'.$style.'>'.htmlspecialchars((string) $cell).'</th>';
                } else {
                    $bg = ($i % 2 === 0) ? '' : ' background-color:#F5F0EB;';
                    $html .= '<td style="'.$bg.'"'.$style.'>'.htmlspecialchars((string) $cell).'</td>';
                }
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        return $html;
    }

    private static function getLabelToGroup(): array
    {
        $map = [];
        foreach (Bahan::activeItems() as $item) {
            $map[$item['nama']] = [$item['kategori'] ?? 'Lainnya', $item['kelompok'] ?? 'Lainnya'];
        }
        return $map;
    }
}

