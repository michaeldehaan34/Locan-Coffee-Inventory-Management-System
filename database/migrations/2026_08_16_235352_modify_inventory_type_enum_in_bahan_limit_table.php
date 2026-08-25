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
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE bahan_limit MODIFY COLUMN inventory_type ENUM('gudang', 'coffee_shop', 'kitchen') NOT NULL DEFAULT 'coffee_shop'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE bahan_limit MODIFY COLUMN inventory_type ENUM('gudang', 'coffee_shop') NOT NULL DEFAULT 'coffee_shop'");
        }
    }
};
