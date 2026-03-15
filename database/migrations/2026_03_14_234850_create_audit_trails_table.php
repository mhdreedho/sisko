<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Membuat tabel audit_trails.
 *
 * Tabel ini mencatat SEMUA aktivitas penting di sistem:
 * - Login berhasil & gagal
 * - Semua aksi CRUD di setiap modul
 * - Perubahan data (field apa, nilai sebelum & sesudah)
 *
 * Hak akses baca: Super Admin only.
 * Tidak ada delete audit trail — ini adalah catatan permanen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();

            // Untuk multi-tenancy: semua data ter-scope per korporasi
            $table->unsignedBigInteger('korporasi_id')->nullable();

            // user_id nullable: null berarti aksi dilakukan oleh sistem
            // (contoh: seeding data awal, cron job, dll)
            $table->unsignedBigInteger('user_id')->nullable();

            // Jenis aksi yang dilakukan
            // Contoh: create, update, delete, view, login, logout, login_failed
            $table->string('aksi', 50);

            // Nama modul yang diakses
            // Contoh: proyek, rap, pr, petty_cash, auth
            $table->string('modul', 100);

            // ID record yang diakses/diubah (nullable: tidak semua aksi punya record spesifik)
            // Contoh: login tidak punya record_id
            $table->unsignedBigInteger('record_id')->nullable();

            // Nama model/tabel yang terkait (untuk polymorphic reference)
            // Contoh: App\Models\Proyek, App\Models\PurchaseRequest
            $table->string('record_type', 200)->nullable();

            // Menyimpan perubahan data dalam format JSON
            // Format: {"nama_field": {"before": "nilai_lama", "after": "nilai_baru"}}
            // Hanya diisi untuk aksi 'update'
            $table->jsonb('perubahan')->nullable();

            // Informasi teknis untuk keperluan keamanan & forensik
            $table->string('ip_address', 45)->nullable(); // 45 karakter: support IPv6
            $table->string('user_agent', 500)->nullable();

            // Tidak ada updated_at karena audit trail tidak pernah diupdate
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};
