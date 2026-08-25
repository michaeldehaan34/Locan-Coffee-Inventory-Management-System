<?php

return [
    /*
     * Daftar shift yang tersedia (single source of truth) — homolog dengan
     * SHIFT_LIST di Flask config.py.
     */
    // 'shift_list' => ['Sekolah', 'Middle 1', 'TPA', 'Middle 2', 'Ronda'],

    /*
     * Minimal foto wajib di-upload per submission Daily Clean.
     * Homolog dengan DAILY_CLEAN_MIN_PHOTOS di Flask config.py.
     */
    'daily_clean_min_photos' => 4,

    /*
     * Retensi foto Daily Clean (hari). File & metadata lebih tua dari nilai
     * ini dibersihkan otomatis. Homolog dengan DAILY_CLEAN_MAX_AGE_DAYS.
     */
    'daily_clean_max_age_days' => 7,

    /*
     * Default limit klasifikasi stok (homolog DEFAULT_LIMIT_HABIS /
     * DEFAULT_LIMIT_TIPIS di Flask modules/bahan_limit.py).
     */
    'default_limit_habis' => 0.0,
    'default_limit_tipis' => 2.0,

    /*
     * Daftar Kategori untuk Master Barang (homolog opsi <select> di template
     * master_bahan.html Flask: Bahan Baku Bar / Bahan Baku Kitchen / Equipment
     * / Lainnya).
     */
    'kategori_list' => ['Bahan Baku Bar', 'Bahan Baku Kitchen', 'Equipment'],

    /*
     * Daftar Satuan untuk Master Barang (homolog opsi <select> satuan di
     * template master_bahan.html Flask).
     */
    'satuan_list' => [
        'mg', 'gram', 'kg',
        'ml', 'liter',
        'pcs', 'unit', 'buah', 'item', 'set', 'pack', 'paket', 'box', 'karton',
        'dus', 'botol', 'kaleng', 'jar', 'pouch', 'sachet', 'bungkus', 'lembar',
        'roll', 'pasang', 'tray', 'cup', 'galon', 'karung'
    ],
];

