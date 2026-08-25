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
        Schema::table('bahan_limit', function (Blueprint $table) {
            $table->dropForeign(['bahan_id']);
            $table->dropPrimary(['bahan_id']);
            $table->enum('inventory_type', ['gudang', 'coffee_shop'])->default('coffee_shop')->after('bahan_id');
            $table->primary(['bahan_id', 'inventory_type']);
            $table->foreign('bahan_id')->references('id')->on('bahan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_limit', function (Blueprint $table) {
            $table->dropForeign(['bahan_id']);
            $table->dropPrimary(['bahan_id', 'inventory_type']);
            $table->dropColumn('inventory_type');
            $table->primary('bahan_id');
            $table->foreign('bahan_id')->references('id')->on('bahan')->onDelete('cascade');
        });
    }
};
