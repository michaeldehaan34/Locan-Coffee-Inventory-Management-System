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
        Schema::table('update_stok', function (Blueprint $table) {
            $table->unsignedInteger('barista_id')->nullable()->after('id');
            $table->foreign('barista_id')->references('id')->on('barista')->nullOnDelete();
        });

        Schema::table('stok_masuk', function (Blueprint $table) {
            $table->unsignedInteger('barista_id')->nullable()->after('id');
            $table->foreign('barista_id')->references('id')->on('barista')->nullOnDelete();
        });

        Schema::table('ambil_bahan_gudang', function (Blueprint $table) {
            $table->unsignedInteger('barista_id')->nullable()->after('id');
            $table->foreign('barista_id')->references('id')->on('barista')->nullOnDelete();
        });

        Schema::table('gudang_kirim_stok', function (Blueprint $table) {
            $table->unsignedInteger('barista_id')->nullable()->after('id');
            $table->foreign('barista_id')->references('id')->on('barista')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('update_stok', function (Blueprint $table) {
            $table->dropForeign(['barista_id']);
            $table->dropColumn('barista_id');
        });

        Schema::table('stok_masuk', function (Blueprint $table) {
            $table->dropForeign(['barista_id']);
            $table->dropColumn('barista_id');
        });

        Schema::table('ambil_bahan_gudang', function (Blueprint $table) {
            $table->dropForeign(['barista_id']);
            $table->dropColumn('barista_id');
        });

        Schema::table('gudang_kirim_stok', function (Blueprint $table) {
            $table->dropForeign(['barista_id']);
            $table->dropColumn('barista_id');
        });
    }
};
