<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('daily_clean', function (Blueprint $table) {
            $table->enum('inventory_type', ['coffee_shop', 'kitchen'])->default('coffee_shop')->after('barista');
        });

        Schema::table('token_listrik', function (Blueprint $table) {
            $table->enum('inventory_type', ['coffee_shop', 'kitchen'])->default('coffee_shop')->after('barista');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('token_listrik', function (Blueprint $table) {
            $table->dropColumn('inventory_type');
        });

        Schema::table('daily_clean', function (Blueprint $table) {
            $table->dropColumn('inventory_type');
        });
    }
};
