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
        Schema::create('ambil_bahan_gudang', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('shift');
            $table->string('barista');
            $table->timestamps();
        });

        Schema::create('ambil_bahan_gudang_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambil_bahan_gudang_id')->constrained('ambil_bahan_gudang')->onDelete('cascade');
            $table->unsignedInteger('bahan_id');
            $table->foreign('bahan_id')->references('id')->on('bahan')->onDelete('cascade');
            $table->decimal('jumlah', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ambil_bahan_gudang_items');
        Schema::dropIfExists('ambil_bahan_gudang');
    }
};
