<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
try {
    \Illuminate\Support\Facades\DB::statement('ALTER TABLE gudang_kirim_stok DROP FOREIGN KEY gudang_kirim_stok_received_by_foreign;');
} catch (\Exception $e) {}
try {
    \Illuminate\Support\Facades\DB::statement('ALTER TABLE gudang_kirim_stok DROP COLUMN received_at, DROP COLUMN received_by;');
} catch (\Exception $e) {}
echo "Dropped\n";
