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
        // Table name preserved from the legacy Flask project so old data stays
        // compatible. The original schema stores `nama_lengkap`, `no_telp`
        // and a `role` enum (barista|manager); there is no password column
        // because login uses the last 6 digits of `no_telp`.
        //
        // A `username` column is added to mirror the `manager` table and to
        // back the role-based login dropdown (a single `username` field is
        // submitted from the login form for both roles).
        Schema::create('barista', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('nama_lengkap')->nullable();
            $table->string('no_telp');
            $table->enum('role', ['manager', 'barista', 'manajemen', 'headbar', 'kitchen', 'headkitchen', 'admin gudang'])->default('barista');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barista');
    }
};