<?php

use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

/**
 * Livewire Component: Login
 *
 * Menangani form login SISKO.
 *
 * Fitur:
 * - Validasi input email & password
 * - Cek status akun (aktif/nonaktif)
 * - Rate limiting: max 5 percobaan per 60 detik per IP+email
 * - Pesan error generic untuk keamanan (tidak membedakan email vs password salah)
 * - Audit trail: catat login berhasil & gagal
 * - Remember me: session panjang
 */

// Tentukan layout yang dipakai halaman ini (layout guest, tanpa sidebar)
new #[Layout('components.layouts.auth')] class extends Component {
    // =========================================================
    // PROPERTIES — Data form yang di-bind ke input
    // =========================================================

    #[
        Validate(
            'required|string|email',
            message: [
                'required' => 'Email wajib diisi.',
                'email' => 'Format email tidak valid.',
            ],
        ),
    ]
    public string $email = '';

    #[
        Validate(
            'required|string',
            message: [
                'required' => 'Password wajib diisi.',
            ],
        ),
    ]
    public string $password = '';

    // Checkbox "Ingat Saya" — jika dicentang, session lebih panjang
    public bool $ingat_saya = false;

    // =========================================================
    // ACTIONS
    // =========================================================

    /**
     * Proses login saat form disubmit.
     * Dipanggil dari tombol submit di template.
     */
    public function login(): void
    {
        // Validasi input terlebih dahulu
        $this->validate();

        // Cek rate limiting sebelum proses login
        // Mencegah brute force attack
        $this->cekRateLimit();

        // Cari user berdasarkan email
        $user = User::where('email', $this->email)->first();

        // Cek apakah email+password cocok
        // Kita cek password dulu SEBELUM cek status akun
        // agar tidak bocor info "email ini terdaftar tapi nonaktif"
        if (!$user || !Auth::validate(['email' => $this->email, 'password' => $this->password])) {
            // Login gagal: catat ke audit trail
            app(AuditTrailService::class)->logLogin(
                email: $this->email,
                berhasil: false,
                userId: $user?->id, // bisa null jika email tidak terdaftar
            );

            // Tambah hitungan percobaan gagal untuk rate limiting
            RateLimiter::hit($this->throttleKey());

            // Pesan error GENERIC — tidak membedakan email salah vs password salah
            // Ini praktik keamanan standar agar penyerang tidak tahu mana yang benar
            $this->addError('email', 'Email atau password yang Anda masukkan salah.');

            return;
        }

        // Sampai sini: email & password cocok
        // Sekarang baru cek status akun
        if (!$user->isAktif()) {
            // Catat percobaan login akun nonaktif
            app(AuditTrailService::class)->logLogin(email: $this->email, berhasil: false, userId: $user->id);

            RateLimiter::hit($this->throttleKey());

            // Pesan error SPESIFIK untuk akun nonaktif
            // (sesuai spesifikasi dokumen)
            $this->addError('email', 'Akun Anda tidak aktif. Silakan hubungi administrator.');

            return;
        }

        // Login berhasil — hapus hitungan rate limit
        RateLimiter::clear($this->throttleKey());

        // Lakukan login dengan Auth facade
        // Parameter kedua ($this->ingat_saya) menentukan durasi session
        Auth::login($user, $this->ingat_saya);

        // Catat login berhasil ke audit trail
        app(AuditTrailService::class)->logLogin(email: $this->email, berhasil: true, userId: $user->id);

        // Regenerate session ID untuk mencegah session fixation attack
        session()->regenerate();

        // Redirect ke dashboard setelah login berhasil
        $this->redirect(route('dashboard'), navigate: true);
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    /**
     * Cek apakah user ini sudah terlalu banyak percobaan login.
     * Max: 5 percobaan per menit per kombinasi email+IP.
     */
    private function cekRateLimit(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return; // Masih dalam batas, lanjut
        }

        // Terlalu banyak percobaan — kirim event Lockout
        event(new Lockout(request()));

        // Hitung berapa detik lagi bisa coba
        $seconds = RateLimiter::availableIn($this->throttleKey());

        // Hentikan proses login dengan pesan error
        $this->addError('email', "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.");

        // Pakai Livewire's cancel untuk menghentikan proses
        $this->dispatch('login-throttled');

        // Throw validation exception agar proses berhenti
        throw \Illuminate\Validation\ValidationException::withMessages([
            'email' => ["Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."],
        ]);
    }

    /**
     * Buat key unik untuk rate limiter.
     * Kombinasi email + IP agar lebih sulit di-bypass.
     */
    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
}; ?>

{{--
    Template Halaman Login
    Menggunakan Flux UI Pro components untuk tampilan yang konsisten.
--}}
<div class="flex min-h-screen items-center justify-center bg-zinc-50 dark:bg-zinc-900">
    <div class="w-full max-w-sm">

        {{-- Logo & Judul --}}
        <div class="mb-8 text-center">
            <div class="mb-4 flex justify-center">
                {{-- Placeholder logo — nanti diganti dengan logo korporasi --}}
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

        {{-- Card Form Login --}}
        <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">

            <h2 class="mb-6 text-lg font-semibold text-zinc-900 dark:text-white">
                Masuk ke Akun Anda
            </h2>

            <form
                wire:submit="login"
                class="space-y-5"
            >

                {{-- Field Email --}}
                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input
                        wire:model="email"
                        type="email"
                        placeholder="nama@email.com"
                        autofocus
                        autocomplete="email"
                    />
                    <flux:error name="email" />
                </flux:field>

                {{-- Field Password --}}
                <flux:field>
                    <div class="flex items-center justify-between">
                        <flux:label>Password</flux:label>
                        {{-- Link lupa password --}}
                        <a
                            href="#"
                            wire:navigate
                            class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400"
                        >
                            Lupa password?
                        </a>
                    </div>
                    <flux:input
                        wire:model="password"
                        type="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        viewable
                    />
                    <flux:error name="password" />
                </flux:field>

                {{-- Checkbox Ingat Saya --}}
                <flux:field variant="inline">
                    <flux:checkbox
                        wire:model="ingat_saya"
                        id="ingat_saya"
                    />
                    <flux:label for="ingat_saya">Ingat saya di perangkat ini</flux:label>
                </flux:field>

                {{-- Tombol Submit --}}
                <flux:button
                    type="submit"
                    variant="primary"
                    class="w-full"
                    wire:loading.attr="disabled"
                    wire:target="login"
                >
                    {{-- Tampilkan loading indicator saat proses login --}}
                    <span
                        wire:loading.remove
                        wire:target="login"
                    >Masuk</span>
                    <span
                        wire:loading
                        wire:target="login"
                    >Memproses...</span>
                </flux:button>

            </form>

        </div>

        {{-- Footer kecil --}}
        <p class="mt-6 text-center text-xs text-zinc-400">
            © {{ date('Y') }} SISKO. All rights reserved.
        </p>

    </div>
</div>
