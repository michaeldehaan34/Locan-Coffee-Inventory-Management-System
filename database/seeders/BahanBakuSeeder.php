<?php

namespace Database\Seeders;

use App\Models\Bahan;
use Illuminate\Database\Seeder;

class BahanBakuSeeder extends Seeder
{
    /**
     * Master data bahan baku sesuai daftar yang diberikan.
     * Format: [kode, nama, kategori, kelompok, satuan].
     */
    private const MASTER_DATA = [
        ['arabica', 'Arabica', 'Bahan Baku Bar', 'Roasted Beans', 'kg'],
        ['house_blend', 'House Blend', 'Bahan Baku Bar', 'Roasted Beans', 'kg'],
        ['galon_amidis', 'Galon Amidis', 'Bahan Baku Bar', 'Water', 'liter'],
        ['fresh_milk', 'Fresh Milk', 'Bahan Baku Bar', 'Milk', 'liter'],
        ['uht_milk', 'UHT Milk', 'Bahan Baku Bar', 'Milk', 'liter'],
        ['condence_milk', 'Condence Milk', 'Bahan Baku Bar', 'Milk', 'liter'],
        ['evaporate_milk', 'Evaporate Milk', 'Bahan Baku Bar', 'Milk', 'liter'],
        ['black_tea', 'Black', 'Bahan Baku Bar', 'Tea', 'bag'],
        ['chamomile_tea', 'Chamomile', 'Bahan Baku Bar', 'Tea', 'bag'],
        ['peach_tea', 'Peach', 'Bahan Baku Bar', 'Tea', 'bag'],
        ['ice_cube', 'Ice Cube', 'Bahan Baku Bar', 'Iced', 'kg'],
        ['ice_cream', 'Ice Cream', 'Bahan Baku Bar', 'Iced', 'ember'],
        ['syrup_caramel', 'Caramel', 'Bahan Baku Bar', 'Syrup', 'liter'],
        ['syrup_coconut', 'Coconut', 'Bahan Baku Bar', 'Syrup', 'liter'],
        ['syrup_cocopandan', 'Cocopandan', 'Bahan Baku Bar', 'Syrup', 'liter'],
        ['syrup_hazelnut', 'Hazelnut', 'Bahan Baku Bar', 'Syrup', 'liter'],
        ['syrup_rose', 'Rose', 'Bahan Baku Bar', 'Syrup', 'liter'],
        ['syrup_strawberry', 'Strawberry', 'Bahan Baku Bar', 'Syrup', 'liter'],
        ['syrup_vanilla', 'Vanilla', 'Bahan Baku Bar', 'Syrup', 'liter'],
        ['juice_apple', 'Apple', 'Bahan Baku Bar', 'Juice', 'liter'],
        ['juice_cranberry', 'Cranberry', 'Bahan Baku Bar', 'Juice', 'liter'],
        ['juice_orange', 'Orange', 'Bahan Baku Bar', 'Juice', 'liter'],
        ['sauce_lemon', 'Lemon', 'Bahan Baku Bar', 'Sauce & Concentrate', 'liter'],
        ['sauce_caramel', 'Caramel Sauce', 'Bahan Baku Bar', 'Sauce & Concentrate', 'liter'],
        ['powder_greentea', 'Greentea', 'Bahan Baku Bar', 'Powder', 'kg'],
        ['powder_chocolate_milk', 'Chocolate Milk', 'Bahan Baku Bar', 'Powder', 'kg'],
        ['powder_red_velvet', 'Red Velvet', 'Bahan Baku Bar', 'Powder', 'kg'],
        ['cocoa_powder', 'Cocoa', 'Bahan Baku Bar', 'Powder', 'gr'],
        ['tepung_maizena', 'Tepung Maizena', 'Bahan Baku Bar', 'Powder', 'gr'],
        ['lychee_fruit', 'Lychee', 'Bahan Baku Bar', 'Fruit', 'kaleng'],
        ['gula_kelapa', 'Gula Kelapa', 'Bahan Baku Bar', 'Sweetener', 'liter'],
        ['gula_pasir', 'Gula Pasir', 'Bahan Baku Bar', 'Sweetener', 'kg'],
        ['gula_sachet', 'Gula Sachet', 'Bahan Baku Bar', 'Sweetener', 'sachet'],
        ['tropicana_slim', 'Tropicana Slim', 'Bahan Baku Bar', 'Sweetener', 'sachet'],
        ['oreo_vanilla', 'Oreo Vanilla', 'Bahan Baku Bar', 'Additional', 'pcs'],
        ['regal_marie', 'Regal Marie', 'Bahan Baku Bar', 'Additional', 'pcs'],
        ['french_fries', 'French Fries', 'Bahan Baku Kitchen', 'Savory', 'kg'],
        ['salt', 'Salt', 'Bahan Baku Kitchen', 'Savory', 'gr'],
        ['parsley', 'Parsley', 'Bahan Baku Kitchen', 'Savory', 'gr'],
        ['cooking_oil', 'Cooking Oil', 'Bahan Baku Kitchen', 'Savory', 'liter'],
        ['sauce_hot', 'Sauce Hot', 'Bahan Baku Kitchen', 'Savory', 'sachet'],
        ['sauce_tomato', 'Sauce Tomato', 'Bahan Baku Kitchen', 'Savory', 'sachet'],
        ['cheesecake_original', 'Cheesecake Original', 'Bahan Baku Kitchen', 'Sweet', 'pcs'],
        ['almond_croissant', 'Almond Croissant', 'Bahan Baku Kitchen', 'Sweet', 'pcs'],
        ['beef_cheese', 'Beef & Cheese', 'Bahan Baku Kitchen', 'Sweet', 'pcs'],
        ['chocolate_nuts_roll', 'Chocolate Nuts Roll', 'Bahan Baku Kitchen', 'Sweet', 'pcs'],
        ['pain_au_chocolate', 'Pain Au Chocolate', 'Bahan Baku Kitchen', 'Sweet', 'pcs'],
        ['triple_cheese', 'Triple Cheese', 'Bahan Baku Kitchen', 'Sweet', 'pcs'],
        ['tabung_gas_elpiji', 'Tabung Gas Elpiji', 'Equipment', 'Equipment', '12kg'],
        ['pure_espresso_machine', 'Pure Espresso Machine', 'Equipment', 'Equipment', 'gr'],
        ['thermal_paper', 'Thermal Paper', 'Equipment', 'Equipment', 'roll'],
        ['trash_bag_s', 'Trash Bag S', 'Equipment', 'Equipment', 'pcs'],
        ['trash_bag_m', 'Trash Bag M', 'Equipment', 'Equipment', 'pcs'],
        ['trash_bag_l', 'Trash Bag L', 'Equipment', 'Equipment', 'pcs'],
        ['pembersih_lantai', 'Pembersih Lantai', 'Equipment', 'Equipment', 'liter'],
        ['pembersih_kaca', 'Pembersih Kaca', 'Equipment', 'Equipment', 'liter'],
        ['pembersih_piring', 'Pembersih Piring', 'Equipment', 'Equipment', 'liter'],
        ['pembersih_tangan', 'Pembersih Tangan', 'Equipment', 'Equipment', 'liter'],
        ['pembersih_kloset', 'Pembersih Kloset', 'Equipment', 'Equipment', 'liter'],
        ['pembersih_kain', 'Pembersih Kain', 'Equipment', 'Equipment', 'liter'],
        ['pembersih_serangga', 'Pembersih Serangga', 'Equipment', 'Equipment', 'kaleng'],
        ['kamper_toilet', 'Kamper Toilet', 'Equipment', 'Equipment', 'pcs'],
        ['spons_cuci', 'Spons Cuci', 'Equipment', 'Equipment', 'pcs'],
        ['pengharum_ruangan', 'Pengharum Ruangan', 'Equipment', 'Equipment', 'kaleng'],
        ['pengharum_otomatis', 'Pengharum Otomatis', 'Equipment', 'Equipment', 'kaleng'],
        ['tisu_kotak', 'Tisu Kotak', 'Equipment', 'Equipment', 'pcs'],
        ['tisu_bulat', 'Tisu Bulat', 'Equipment', 'Equipment', 'pcs'],
        ['sedotan_lancip', 'Sedotan Lancip', 'Equipment', 'Equipment', 'pcs'],
        ['sedotan_stierer', 'Sedotan Stierer', 'Equipment', 'Equipment', 'pcs'],
        ['kantong_plastik_m', 'Kantong Plastik M', 'Equipment', 'Equipment', 'pcs'],
        ['kantong_plastik_l', 'Kantong Plastik L', 'Equipment', 'Equipment', 'pcs'],
        ['plastik_wrap', 'Plastik Wrap', 'Equipment', 'Equipment', 'roll'],
        ['plastik_sarung_tangan', 'Plastik Sarung Tangan', 'Equipment', 'Equipment', 'pcs'],
        ['plastik_klip', 'Plastik Klip', 'Equipment', 'Equipment', 'pcs'],
        ['plastik_sealer_cup', 'Plastik Sealer Cup', 'Equipment', 'Equipment', 'roll'],
        ['plastik_cup_14oz', 'Plastik Cup 14oz', 'Equipment', 'Equipment', 'pcs'],
        ['paper_cup_8oz', 'Paper Cup 8oz', 'Equipment', 'Equipment', 'pcs'],
        ['tutup_paper_cup', 'Tutup Paper Cup', 'Equipment', 'Equipment', 'pcs'],
        ['baterai_aa', 'Baterai AA', 'Equipment', 'Equipment', 'pcs'],
        ['baterai_aaa', 'Baterai AAA', 'Equipment', 'Equipment', 'pcs'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $added = 0;

        foreach (self::MASTER_DATA as $urutan => [$kode, $nama, $kategori, $kelompok, $satuan]) {
            $bahan = Bahan::updateOrCreate(
                ['kode' => $kode],
                [
                    'nama' => $nama,
                    'kategori' => $kategori,
                    'kelompok' => $kelompok,
                    'satuan' => $satuan,
                    'urutan' => $urutan,
                    'sort_order' => $urutan,
                    'is_active' => true,
                ]
            );

            if ($bahan->wasRecentlyCreated) {
                $added++;
            }
        }

        $this->command->info('Bahan baku selesai. Ditambahkan ' . $added . ' data baru. Total data bahan: ' . Bahan::count());
    }
}