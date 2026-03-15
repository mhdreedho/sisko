<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/**
 * Livewire Component: Reset Password
 *
 * Menangani form input password baru setelah user klik link di email.
 *
 * Alur:
 * 1. User klik link di email → diarahkan ke /reset-password/{token}?email=xxx
 * 2. Token & email diambil dari URL, langsung dicek validitasnya di mount()
 * 3a. Token tidak valid/expired → tampilkan halaman expired langsung
 * 3b. Token valid → tampilkan form input password baru
 * 4. User input password baru + konfirmasi → submit
 * 5. Redirect ke login dengan pesan sukses
 *
 * Catatan:
 * - Token expired: 60 menit
 * - Token hanya bisa dipakai sekali — setelah dipakai langsung invalid
 * - Password baru mengikuti aturan Password::default() dari AppServiceProvider
 *   (development: tidak ada batasan, production: min 12 karakter + mixed)
 */

new #[Layout('components.layouts.auth')] class extends Component {
    // =========================================================
    // PROPERTIES
    // =========================================================

    // Token reset dari URL — dikunci agar tidak bisa dimanipulasi dari frontend
    #[Locked]
    public string $token = '';

    // Email dari URL — dikunci agar tidak bisa dimanipulasi dari frontend
    #[Locked]
    public string $email = '';

    // Flag: token tidak valid atau sudah expired/dipakai
    // Jika true → tampilkan halaman expired, sembunyikan form
    public bool $tokenTidakValid = false;

    // Password baru yang diinput user
    public string $password = '';

    // Konfirmasi password baru — harus sama dengan $password
    public string $password_confirmation = '';

    // =========================================================
    // LIFECYCLE
    // =========================================================

    /**
     * Inisialisasi component saat pertama kali dimuat.
     * Ambil token dari route parameter dan email dari query string URL.
     * Langsung cek validitas token — jika tidak valid, tampilkan halaman expired.
     */
    public function mount(string $token): void
    {
        // Token diambil dari route parameter /reset-password/{token}
        $this->token = $token;

        // Email diambil dari query string ?email=xxx yang dikirim bersama link
        $this->email = request()->string('email')->value();

        // Cek validitas token langsung saat halaman dibuka
        // Menggunakan Password Broker untuk validasi token vs DB
        // tokenExists() mengecek: token ada di tabel password_reset_tokens
        // dan belum expired (< 60 menit)
        $tokenValid = Password::tokenExists(
            \App\Models\User::where('email', $this->email)->first() ?? new \App\Models\User(),
            $this->token,
        );

        if (!$tokenValid) {
            // Token tidak valid, expired, atau sudah dipakai
            // Langsung set flag agar tampilan form disembunyikan
            $this->tokenTidakValid = true;
        }
    }

    // =========================================================
    // ACTIONS
    // =========================================================

    /**
     * Proses reset password.
     * Dipanggil saat form disubmit.
     */
    public function resetPassword(): void
    {
        // Validasi input password baru
        // Password::default() mengikuti aturan di AppServiceProvider:
        // - Development: tidak ada batasan khusus (null)
        // - Production: min 12 karakter, mixed case, angka, simbol
        $this->validate(
            [
                'password'              => ['required', 'string', \Illuminate\Validation\Rules\Password::default(), 'confirmed'],
                'password_confirmation' => ['required', 'string'],
            ],
            [
                'password.required'              => 'Password baru wajib diisi.',
                'password.confirmed'             => 'Konfirmasi password tidak cocok.',
                'password_confirmation.required' => 'Konfirmasi password wajib diisi.',
            ],
        );

        // Proses reset password via Laravel Password Broker
        // Broker akan:
        // 1. Validasi token (ada di DB & belum expired)
        // 2. Cari user berdasarkan email
        // 3. Update password user
        // 4. Hapus token dari tabel password_reset_tokens (token jadi invalid setelahnya)
        $status = Password::reset(
            [
                'email'                 => $this->email,
                'password'              => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token'                 => $this->token,
            ],
            function ($user, $password) {
                // Update password user
                // forceFill dipakai agar bisa set field yang di-guard sekalipun
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60), // invalidate semua session lama
                ])->save();

                // Trigger event PasswordReset untuk keperluan lain (notifikasi, dll)
                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            // Berhasil → redirect ke login dengan pesan sukses via session flash
            session()->flash('status', 'Password berhasil direset. Silakan login dengan password baru Anda.');
            $this->redirect(route('login'), navigate: true);
        } else {
            // Gagal (token expired di tengah jalan, dll) → set flag expired
            $this->tokenTidakValid = true;
        }
    }
}; ?>

{{--
    Template Halaman Reset Password
    Konsisten dengan tampilan halaman login & forgot-password.
--}}
<div class="flex min-h-screen items-center justify-center bg-zinc-50 dark:bg-zinc-900">
    <div class="w-full max-w-sm">

        {{-- Logo & Judul --}}
        <div class="mb-8 text-center">
            <div class="mb-4 flex justify-center">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white font-bold text-xl">
                    S
                </div>
            </div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">SISKO</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Sistem Informasi Manajemen Kontraktor
            </p>
        </div>

        {{-- Card Konten --}}
        <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">

            @if ($tokenTidakValid)
                {{-- =============================================
                    TAMPILAN TOKEN EXPIRED / TIDAK VALID
                    Muncul langsung saat halaman dibuka jika token bermasalah
                ============================================= --}}
                <div class="text-center">

                    {{-- Icon silang merah --}}
                    <div class="mb-4 flex justify-center">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                            <svg
                                class="h-6 w-6 text-red-600 dark:text-red-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </div>
                    </div>

                    <h2 class="mb-2 text-lg font-semibold text-zinc-900 dark:text-white">
                        Link Tidak Valid
                    </h2>

                    <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                        Link reset password ini sudah <strong>kadaluarsa</strong> atau
                        sudah pernah digunakan. Silakan minta link baru.
                    </p>

                    <flux:button
                        href="{{ route('password.request') }}"
                        wire:navigate
                        variant="primary"
                        class="w-full"
                    >
                        Minta Link Baru
                    </flux:button>

                    <div class="mt-4">
                        <a
                            href="{{ route('login') }}"
                            wire:navigate
                            class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                        >
                            ← Kembali ke Login
                        </a>
                    </div>

                </div>

            @else
                {{-- =============================================
                    TAMPILAN FORM RESET PASSWORD
                    Muncul jika token masih valid
                ============================================= --}}
                <h2 class="mb-2 text-lg font-semibold text-zinc-900 dark:text-white">
                    Reset Password
                </h2>

                <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                    Masukkan password baru untuk akun
                    <strong class="text-zinc-700 dark:text-zinc-300">{{ $email }}</strong>.
                </p>

                <form
                    wire:submit="resetPassword"
                    class="space-y-5"
                >
                    {{-- Field Password Baru --}}
                    <flux:field>
                        <flux:label>Password Baru</flux:label>
                        <flux:input
                            wire:model="password"
                            type="password"
                            placeholder="••••••••"
                            autofocus
                            autocomplete="new-password"
                            viewable
                        />
                        <flux:error name="password" />
                    </flux:field>

                    {{-- Field Konfirmasi Password --}}
                    <flux:field>
                        <flux:label>Konfirmasi Password Baru</flux:label>
                        <flux:input
                            wire:model="password_confirmation"
                            type="password"
                            placeholder="••••••••"
                            autocomplete="new-password"
                            viewable
                        />
                        <flux:error name="password_confirmation" />
                    </flux:field>

                    {{-- Tombol Submit --}}
                    <flux:button
                        type="submit"
                        variant="primary"
                        class="w-full"
                        wire:loading.attr="disabled"
                        wire:target="resetPassword"
                    >
                        <span wire:loading.remove wire:target="resetPassword">Reset Password</span>
                        <span wire:loading wire:target="resetPassword">Memproses...</span>
                    </flux:button>

                </form>

                {{-- Link kembali ke login --}}
                <div class="mt-5 text-center">
                    <a
                        href="{{ route('login') }}"
                        wire:navigate
                        class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                    >
                        ← Kembali ke Login
                    </a>
                </div>

            @endif

        </div>

        {{-- Footer kecil --}}
        <p class="mt-6 text-center text-xs text-zinc-400">
            © {{ date('Y') }} SISKO. All rights reserved.
        </p>

    </div>
</div>
