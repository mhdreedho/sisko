<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    // Tambah HasRoles dari Spatie untuk manajemen role & permission
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * Kolom yang boleh diisi secara mass assignment.
     * Tambah korporasi_id dan status dari kolom baru SISKO.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'korporasi_id', // untuk multi-tenancy
        'status',       // aktif/nonaktif — kontrol akses login
    ];

    /**
     * Kolom yang disembunyikan saat model dikonversi ke array/JSON.
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Casting otomatis untuk kolom tertentu.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // =========================================================
    // HELPER METHODS
    // =========================================================

    /**
     * Ambil inisial nama user — dipakai untuk avatar.
     * Contoh: "Muhammad Reedho" → "MR"
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Cek apakah akun user ini aktif.
     * Dipakai di login component sebelum proses login diizinkan.
     */
    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }

    // =========================================================
    // RELASI
    // =========================================================

    /**
     * Semua catatan audit trail yang dibuat oleh user ini.
     */
    public function auditTrails(): HasMany
    {
        return $this->hasMany(AuditTrail::class);
    }
}
