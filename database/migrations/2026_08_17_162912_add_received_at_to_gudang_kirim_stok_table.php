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
        Schema::table('gudang_kirim_stok', function (Blueprint $table) {
            $table->timestamp('received_at')->nullable()->after('status');
            $table->unsignedInteger('received_by')->nullable()->after('received_at');

            // Foreign key to barista (assuming users who receive are baristas/headbar/headkitchen)
            $table->foreign('received_by')->references('id')->on('barista')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gudang_kirim_stok', function (Blueprint $table) {
            $table->dropForeign(['received_by']);
            $table->dropColumn(['received_at', 'received_by']);
        });
    }
};
