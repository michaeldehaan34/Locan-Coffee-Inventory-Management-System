<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $beforeCount = DB::table('barista')->where('role', 'manager')->count();
        echo "Jumlah role manager sebelum migration: {$beforeCount}\n";

        // 1. Update data existing
        DB::table('barista')->where('role', 'manager')->update(['role' => 'manajemen']);
        
        // 2. Modify enum
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE barista MODIFY COLUMN role ENUM('barista', 'manajemen', 'headbar', 'kitchen', 'headkitchen', 'admin gudang') DEFAULT 'barista'");
        }

        $afterCount = DB::table('barista')->where('role', 'manajemen')->count();
        echo "Jumlah role manajemen setelah migration: {$afterCount}\n";

        if ($beforeCount !== $afterCount) {
            echo "WARNING: Jumlah data tidak sama!\n";
        } else {
            echo "SUCCESS: Seluruh data berhasil dimigrasi.\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert enum back (might lose headbar etc if they exist, but for rollback it's best effort)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE barista MODIFY COLUMN role ENUM('manager', 'barista') DEFAULT 'barista'");
        }
        
        // Revert data
        DB::table('barista')->where('role', 'manajemen')->update(['role' => 'manager']);
    }
};
