<?php

use App\Services\AuditTrailService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

/**
 * Livewire Component: Logout
 *
 * Komponen ini sifatnya hanya action — tidak ada tampilan sendiri.
 * Dipakai sebagai komponen yang di-embed di dalam layout/navigasi,
 * atau dipanggil via tombol di sidebar/header.
 *
 * Cara pakai di Blade template:
 *   <livewire:auth.logout />
 *
 * Atau cukup pakai action-nya saja di komponen lain:
 *   wire:click="$dispatch('logout')"
 */
new class extends Component {
    /**
     * Proses logout.
     *
     * Urutan yang benar:
     * 1. Catat ke audit trail DULU (sebelum session dihapus, karena masih butuh user info)
     * 2. Logout via Auth facade
     * 3. Invalidate session (hapus semua data session)
     * 4. Regenerate CSRF token (keamanan)
     * 5. Redirect ke halaman login
     */
    public function logout(): void
    {
        // Catat logout ke audit trail SEBELUM session dihapus
        // Urutan ini penting! Setelah Auth::logout(), Auth::user() sudah null
        app(AuditTrailService::class)->logLogout();

        // Logout — hapus informasi autentikasi dari session
        Auth::logout();

        // Invalidate seluruh session agar tidak ada data yang tersisa
        session()->invalidate();

        // Regenerate CSRF token untuk mencegah CSRF token fixation
        session()->regenerateToken();

        // Redirect ke halaman login
        $this->redirect(route('login'), navigate: true);
    }
}; ?>

{{--
    Template Logout — hanya sebuah tombol/link.
    Tampilan ini dipakai di dalam sidebar atau header.
    Anda bebas mengubah tampilan sesuai kebutuhan layout.
--}}
<div>
    <flux:button
        wire:click="logout"
        wire:confirm="Yakin ingin keluar dari SISKO?"
        variant="ghost"
        class="w-full justify-start text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950"
        icon="arrow-right-start-on-rectangle"
    >
        Keluar
    </flux:button>
</div>
