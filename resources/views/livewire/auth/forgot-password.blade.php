<?php

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Livewire Component: Forgot Password (Lupa Password)
 *
 * Menangani form permintaan link reset password via email atau username.
 *
 * Alur:
 * 1. User input email ATAU username
 * 2. Sistem cari user berdasarkan input
 * 3a. Jika tidak ditemukan → tampilkan error "tidak terdaftar"
 * 3b. Jika ditemukan → kirim link reset ke email, tampilkan konfirmasi
 *
 * Aturan tampilan setelah submit:
 * - Input email, user ditemukan   → pesan sukses biasa (tanpa sensor)
 * - Input username, user ditemukan → pesan sukses + email tersensor
 * - User tidak ditemukan          → pesan error tegas di form
 *
 * Format sensor email:
 * - "mhdreedho@gmail.com" → "m*******o@gmail.com"
 * - Karakter pertama & terakhir sebelum @ tetap terlihat
 * - Tengahnya diganti bintang sejumlah karakter yang disembunyikan
 */

new #[Layout('components.layouts.auth')] class extends Component {
    // =========================================================
    // PROPERTIES
    // =========================================================

    // Field input — bisa berisi username atau email
    public string $login = '';

    // Flag: link sudah berhasil dikirim → tampilkan halaman konfirmasi
    public bool $terkirim = false;

    // Email tersensor — hanya diisi jika user input username & user ditemukan
    // Kosong jika input email (tidak perlu sensor) atau user tidak ditemukan
    public string $emailTersensor = '';

    // =========================================================
    // ACTIONS
    // =========================================================

    /**
     * Proses pengiriman link reset password.
     * Dipanggil saat form disubmit.
     */
    public function kirimLink(): void
    {
        // Validasi input tidak boleh kosong
        $this->validate(['login' => ['required', 'string']], ['login.required' => 'Username atau email wajib diisi.']);

        // Deteksi otomatis: mengandung '@' → email, tidak → username
        $isEmail = Str::contains($this->login, '@');

        // Cari user berdasarkan field yang terdeteksi
        $user = $isEmail ? User::where('email', $this->login)->first() : User::where('username', $this->login)->first();

        // Jika user tidak ditemukan → tampilkan error tegas di form
        // Tidak redirect ke halaman sukses — user harus tahu input salah
        if (!$user) {
            $this->addError('login', $isEmail ? 'Email ini tidak terdaftar di sistem.' : 'Username ini tidak terdaftar di sistem.');
            return;
        }

        // User ditemukan → kirim link reset via Laravel Password Broker
        // Password Broker akan generate token & kirim email notifikasi
        Password::sendResetLink(['email' => $user->email]);

        // Siapkan email tersensor HANYA jika user input username
        // Jika input email → tidak perlu sensor (user sudah tahu emailnya)
        $this->emailTersensor = $isEmail ? '' : $this->sensorEmail($user->email);

        // Tandai sudah terkirim → tampilkan halaman konfirmasi
        $this->terkirim = true;
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    /**
     * Sensor email untuk ditampilkan ke user yang login via username.
     *
     * Aturan:
     * - Bagian sebelum '@': karakter pertama & terakhir tampil, tengah jadi bintang
     * - Bagian setelah '@' (domain): tampil apa adanya
     *
     * Contoh:
     * - "mhdreedho@gmail.com" → "m*******o@gmail.com"
     * - "ab@gmail.com"        → "a*@gmail.com"
     * - "a@gmail.com"         → "a@gmail.com"
     */
    private function sensorEmail(string $email): string
    {
        [$lokal, $domain] = explode('@', $email, 2);

        $panjang = strlen($lokal);

        if ($panjang <= 1) {
            // 1 karakter — tidak ada yang bisa disensor
            $lokalTersensor = $lokal;
        } elseif ($panjang === 2) {
            // 2 karakter — tampilkan pertama saja, kedua disensor
            $lokalTersensor = $lokal[0] . '*';
        } else {
            // Lebih dari 2 karakter — pertama & terakhir tampil, tengah bintang
            $bintang = str_repeat('*', $panjang - 2);
            $lokalTersensor = $lokal[0] . $bintang . $lokal[$panjang - 1];
        }

        return $lokalTersensor . '@' . $domain;
    }
}; ?>

{{--
    Template Halaman Lupa Password
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

            @if ($terkirim)
                {{-- =============================================
                    TAMPILAN KONFIRMASI — Link Berhasil Dikirim
                ============================================= --}}
                <div class="text-center">

                    {{-- Icon centang hijau --}}
                    <div class="mb-4 flex justify-center">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                            <svg
                                class="h-6 w-6 text-green-600 dark:text-green-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                        </div>
                    </div>

                    <h2 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-white">
                        Link Terkirim!
                    </h2>

                    @if ($emailTersensor)
                        {{-- Input username — tampilkan email tersensor --}}
                        <p class="mb-2 text-sm text-zinc-500 dark:text-zinc-400">
                            Link reset password telah dikirimkan ke:
                        </p>
                        <p class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">
                            {{ $emailTersensor }}
                        </p>
                    @else
                        {{-- Input email — tampilkan email asli tanpa sensor --}}
                        <p class="mb-2 text-sm text-zinc-500 dark:text-zinc-400">
                            Link reset password telah dikirimkan ke:
                        </p>
                        <p class="mb-4 font-semibold text-zinc-700 dark:text-zinc-300">
                            {{ $login }}
                        </p>
                    @endif

                    <p class="mb-6 text-xs text-zinc-400 dark:text-zinc-500">
                        Silakan cek inbox atau folder spam.<br>
                        Link akan kadaluarsa dalam <strong>60 menit</strong>.
                    </p>

                    <flux:button
                        href="{{ route('login') }}"
                        wire:navigate
                        variant="ghost"
                        class="w-full"
                    >
                        Kembali ke Halaman Login
                    </flux:button>

                </div>
            @else
                {{-- =============================================
                    TAMPILAN FORM INPUT
                ============================================= --}}
                <h2 class="mb-2 text-lg font-semibold text-zinc-900 dark:text-white">
                    Lupa Password?
                </h2>

                <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                    Masukkan username atau email Anda. Link reset password akan dikirim ke email terdaftar.
                </p>

                <form
                    wire:submit="kirimLink"
                    class="space-y-5"
                >
                    {{-- Field Username atau Email --}}
                    <flux:field>
                        <flux:label>Username atau Email</flux:label>
                        <flux:input
                            wire:model="login"
                            type="text"
                            placeholder="username atau nama@email.com"
                            autofocus
                            autocomplete="username"
                        />
                        <flux:error name="login" />
                    </flux:field>

                    {{-- Tombol Kirim --}}
                    <flux:button
                        type="submit"
                        variant="primary"
                        class="w-full"
                        wire:loading.attr="disabled"
                        wire:target="kirimLink"
                    >
                        <span
                            wire:loading.remove
                            wire:target="kirimLink"
                        >Kirim Link Reset Password</span>
                        <span
                            wire:loading
                            wire:target="kirimLink"
                        >Mengirim...</span>
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
