<?php

use Illuminate\Support\Facades\Session;

if (! function_exists('asset_url')) {
    /**
     * Generate an asset URL with the configured asset URL prefix.
     */
    function asset_url(string $path): string
    {
        return rtrim(config('app.asset_url', ''), '/').'/'.ltrim($path, '/');
    }
}

if (! function_exists('flash_success')) {
    /**
     * Queue a success flash message (consumed by the role layout toast system).
     */
    function flash_success(string $message): void
    {
        flash_message('success', $message);
    }
}

if (! function_exists('flash_danger')) {
    /**
     * Queue a danger/error flash message.
     */
    function flash_danger(string $message): void
    {
        flash_message('danger', $message);
    }
}

if (! function_exists('flash_message')) {
    /**
     * Push a flash message into the __flash session bag used by the layout.
     *
     * @param  'success'|'danger'|'info'|'warning'  $type
     */
    function flash_message(string $type, string $message): void
    {
        $bag = Session::get('__flash', []);
        $bag[] = ['type' => $type, 'msg' => $message];
        Session::flash('__flash', $bag);
    }
}

if (! function_exists('shift_list')) {
    /**
     * Daftar shift yang tersedia (single source of truth).
     *
     * @return array<int, string>
     */
    function shift_list(): array
    {
        return config('lotra.shift_list', []);
    }
}

if (! function_exists('is_valid_shift')) {
    /**
     * Validasi apakah nilai shift termasuk daftar shift yang diizinkan.
     */
    function is_valid_shift(?string $shift): bool
    {
        if ($shift === null || trim($shift) === '') {
            return false;
        }

        return true;
    }
}

if (! function_exists('is_valid_date')) {
    /**
     * Validasi apakah string adalah tanggal valid dengan format Y-m-d.
     *
     * Menggunakan DateTime::createFromFormat secara strict sehingga tanggal
     * tidak valid seperti "2026-13-45" atau "abc" ditolak (berbeda dengan
     * sekadar mengecek string tidak kosong).
     */
    function is_valid_date(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        $value = trim($value);
        if ($value === '') {
            return false;
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        if ($dt === false) {
            return false;
        }

        // Pastikan tidak ada sisa karakter dan tanggal benar-benar ada.
        return $dt->format('Y-m-d') === $value;
    }
}

if (! function_exists('format_kwh')) {
    /**
     * Format nilai numerik kWh lengkap dengan satuan (homolog filter Jinja kwh).
     */
    function format_kwh($value): string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return '-';
        }

        $num = (float) $value;

        return (floor($num) == $num ? (int) $num : $num).' kWh';
    }
}

if (! function_exists('format_tanggal_id')) {
    /**
     * Format tanggal ISO 'YYYY-MM-DD' menjadi 'DD NamaBulan YYYY'
     * dengan nama bulan Bahasa Indonesia (homolog Flask _format_tanggal_id).
     *
     * Contoh: '2026-07-12' -> '12 Juli 2026'
     */
    function format_tanggal_id(string $iso): string
    {
        $namaBulan = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        try {
            $parts = explode('-', $iso);
            if (count($parts) !== 3) {
                return $iso;
            }

            $y = (int) $parts[0];
            $m = (int) $parts[1];
            $d = (int) $parts[2];

            if ($m < 1 || $m > 12) {
                return $iso;
            }

            return $d.' '.$namaBulan[$m - 1].' '.$y;
        } catch (\Throwable $e) {
            return $iso;
        }
    }
}
