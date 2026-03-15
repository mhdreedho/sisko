<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration — tambah kolom ke tabel users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom korporasi_id: untuk multi-tenancy (siap SaaS V2)
            // Nullable karena Super Admin mungkin tidak terikat korporasi tertentu
            // Diletakkan setelah kolom 'id' agar urutan kolom rapi
            $table->unsignedBigInteger('korporasi_id')->nullable()->after('id');

            // Kolom status: kontrol akses login
            // aktif = bisa login, nonaktif = tidak bisa login
            // Default 'aktif' agar user yang sudah ada tidak langsung terkunci
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif')->after('remember_token');
        });
    }

    /**
     * Batalkan migration — hapus kolom yang ditambahkan.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['korporasi_id', 'status']);
        });
    }
};
