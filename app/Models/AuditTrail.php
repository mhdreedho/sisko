<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model AuditTrail
 *
 * Merepresentasikan satu baris catatan aktivitas di sistem.
 * Model ini HANYA bisa dibuat (create), tidak ada update atau delete.
 */
class AuditTrail extends Model
{
    // Tidak ada updated_at pada tabel ini
    const UPDATED_AT = null;

    /**
     * Kolom yang boleh diisi secara mass assignment.
     * Semua kolom tabel kita daftarkan di sini karena
     * AuditTrailService yang akan handle pengisian datanya.
     */
    protected $fillable = [
        'korporasi_id',
        'user_id',
        'aksi',
        'modul',
        'record_id',
        'record_type',
        'perubahan',
        'ip_address',
        'user_agent',
    ];

    /**
     * Casting kolom agar otomatis dikonversi ke tipe yang tepat saat diakses.
     * 'perubahan' disimpan sebagai JSONB di PostgreSQL,
     * tapi saat diakses via PHP otomatis jadi array.
     */
    protected $casts = [
        'perubahan'  => 'array',
        'created_at' => 'datetime',
    ];

    // =========================================================
    // RELASI
    // =========================================================

    /**
     * Relasi ke User yang melakukan aksi.
     * Nullable: bisa null jika aksi dilakukan sistem.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
