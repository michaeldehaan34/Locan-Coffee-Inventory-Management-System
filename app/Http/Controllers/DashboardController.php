<?php

namespace App\Http\Controllers;

use App\Services\StockAnalytics;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the manager dashboard (Dashboard Analytics).
     *
     * Widgets are migrated from the original Flask `manager_dashboard.html`:
     *   1. Ringkasan Statistik  -> Barang Aman / Tipis / Habis (from latest stok)
     *   2. Top Barang Paling Sering Habis
     *   3. Top Barang Hampir Habis
     *   4. Aktivitas Barista (jumlah update stok per barista)
     *
     * All figures are computed dynamically from the `update_stok` table via
     * the StockAnalytics service (Single Source of Truth), using the
     * per-bahan limit thresholds stored in `bahan_limit`. No dummy data.
     */
    public function dashboard(): View
    {
        $data = StockAnalytics::dashboard();

        return view('manager.dashboard.dashboard', [
            'title' => 'Dashboard Manager',
            'managerName' => session('name', 'Manager'),
            'has_data' => $data['has_data'],
            'bahan_aman' => $data['bahan_aman'],
            'bahan_tipis' => $data['bahan_tipis'],
            'bahan_habis' => $data['bahan_habis'],
            'limit_habis' => StockAnalytics::DEFAULT_LIMIT_HABIS,
            'limit_tipis' => StockAnalytics::DEFAULT_LIMIT_TIPIS,
            'top_barang_habis' => $data['top_barang_habis'],
            'top_barang_tipis' => $data['top_barang_tipis'],
            'top_aktivitas_barista' => $data['top_aktivitas_barista'],
            'global_stock' => $data['global_stock'] ?? [],
        ]);
    }
}