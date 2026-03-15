<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AuditTrailService
 *
 * Bertanggung jawab mencatat semua aktivitas penting di sistem.
 * Dipanggil dari:
 * - Livewire components (untuk aksi CRUD)
 * - Login & Logout (dicatat manual karena tidak lewat Observer)
 *
 * Cara pakai:
 *   app(AuditTrailService::class)->log('create', 'proyek', $proyek->id);
 *   app(AuditTrailService::class)->logLogin($user, true);
 */
class AuditTrailService
{
    /**
     * Catat satu entri audit trail umum.
     *
     * @param string      $aksi       Jenis aksi: create, update, delete, view, dll
     * @param string      $modul      Nama modul: proyek, rap, pr, dll
     * @param int|null    $recordId   ID record yang diakses (opsional)
     * @param string|null $recordType Nama class model (opsional)
     * @param array|null  $perubahan  Data perubahan untuk aksi 'update' (opsional)
     */
    public function log(
        string $aksi,
        string $modul,
        ?int $recordId = null,
        ?string $recordType = null,
        ?array $perubahan = null
    ): void {
        // Ambil request aktif untuk mendapatkan IP dan User Agent
        $request = request();

        AuditTrail::create([
            'korporasi_id' => Auth::check() ? Auth::user()->korporasi_id : null,
            'user_id'      => Auth::id(), // null jika belum login
            'aksi'         => $aksi,
            'modul'        => $modul,
            'record_id'    => $recordId,
            'record_type'  => $recordType,
            // Perubahan hanya diisi jika ada (aksi update)
            'perubahan'    => $perubahan,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);
    }

    /**
     * Catat event login.
     * Dipanggil manual dari Livewire Login component.
     *
     * @param string $email    Email yang digunakan untuk login (untuk kasus gagal, user belum ada)
     * @param bool   $berhasil true = login berhasil, false = gagal
     * @param int|null $userId ID user jika berhasil ditemukan (untuk kasus gagal juga bisa null)
     */
    public function logLogin(string $email, bool $berhasil, ?int $userId = null): void
    {
        $request = request();

        AuditTrail::create([
            // korporasi_id diambil dari user jika berhasil
            'korporasi_id' => $userId ? \App\Models\User::find($userId)?->korporasi_id : null,
            'user_id'      => $userId,
            // Aksi dibedakan: 'login' untuk berhasil, 'login_failed' untuk gagal
            'aksi'         => $berhasil ? 'login' : 'login_failed',
            'modul'        => 'auth',
            'record_id'    => null,
            'record_type'  => null,
            // Simpan email di perubahan agar ada jejak siapa yang mencoba login gagal
            'perubahan'    => ['email' => $email],
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);
    }

    /**
     * Catat event logout.
     * Dipanggil manual dari Livewire Logout action.
     */
    public function logLogout(): void
    {
        $request = request();
        $user    = Auth::user();

        AuditTrail::create([
            'korporasi_id' => $user?->korporasi_id,
            'user_id'      => $user?->id,
            'aksi'         => 'logout',
            'modul'        => 'auth',
            'record_id'    => null,
            'record_type'  => null,
            'perubahan'    => null,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);
    }

    /**
     * Shortcut untuk mencatat perubahan data (aksi update).
     * Membandingkan data lama vs data baru dan menyimpan perbedaannya saja.
     *
     * @param string $modul      Nama modul
     * @param int    $recordId   ID record yang diupdate
     * @param string $recordType Nama class model
     * @param array  $dataLama   Data sebelum diubah
     * @param array  $dataBaru   Data setelah diubah
     */
    public function logUpdate(
        string $modul,
        int $recordId,
        string $recordType,
        array $dataLama,
        array $dataBaru
    ): void {
        // Hanya simpan field yang benar-benar berubah, bukan semua field
        $perubahan = [];
        foreach ($dataBaru as $field => $nilaiBaru) {
            $nilaiLama = $dataLama[$field] ?? null;
            if ($nilaiLama !== $nilaiBaru) {
                $perubahan[$field] = [
                    'before' => $nilaiLama,
                    'after'  => $nilaiBaru,
                ];
            }
        }

        // Hanya catat jika ada yang benar-benar berubah
        if (! empty($perubahan)) {
            $this->log('update', $modul, $recordId, $recordType, $perubahan);
        }
    }
}
