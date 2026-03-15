<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration — tambah kolom username ke tabel users.
     *
     * Kenapa nullable dulu?
     * Karena sudah ada data user (Super Admin) di database.
     * Jika langsung not null tanpa default, migration akan gagal
     * karena row yang sudah ada tidak punya nilai username.
     * Setelah migration, kita update manual via tinker.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom username: untuk login alternatif selain email
            // - nullable: agar migration tidak gagal pada data lama
            // - unique: tidak boleh ada 2 user dengan username sama
            // - after('name'): diletakkan setelah kolom name agar urutan rapi
            $table->string('username')->nullable()->unique()->after('name');
        });
    }

    /**
     * Batalkan migration — hapus kolom username.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
