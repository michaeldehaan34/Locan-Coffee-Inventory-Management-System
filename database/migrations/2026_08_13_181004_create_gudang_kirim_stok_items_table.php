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
        Schema::create('gudang_kirim_stok_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gudang_kirim_stok_id');
            $table->unsignedInteger('bahan_id');
            $table->float('jumlah')->default(0);
            $table->timestamps();

            $table->foreign('gudang_kirim_stok_id')->references('id')->on('gudang_kirim_stok')->onDelete('cascade');
            $table->foreign('bahan_id')->references('id')->on('bahan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gudang_kirim_stok_items');
    }
};
