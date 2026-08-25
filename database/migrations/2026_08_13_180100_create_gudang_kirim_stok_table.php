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
        Schema::create('gudang_kirim_stok', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->nullable();
            $table->string('manager', 100)->nullable();
            $table->enum('status', ['pending', 'diterima'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gudang_kirim_stok');
    }
};
