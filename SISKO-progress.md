# SISKO — Progress & Catatan Implementasi

> **Tujuan:** Memori kerja Claude Desktop antar sesi.
> Baca file ini di awal setiap sesi untuk tahu kondisi terakhir project.
> Update file ini di akhir setiap sesi setelah modul selesai.

_Update terakhir: Maret 2026_

---

## CARA BACA FILE INI (Awal Sesi)

Gunakan MCP filesystem — bukan GitHub MCP. Perintah di awal sesi:

> "Baca file `\\wsl.localhost\Ubuntu\home\mhdreedho\projects\sisko\SISKO-progress.md`"

**Catatan path penting:**
- Path WSL untuk MCP filesystem → `\\wsl.localhost\Ubuntu\home\mhdreedho\projects\sisko\`
- Jangan pakai path Linux-style (`/home/mhdreedho/...`) — tidak akan terbaca

---

## STATUS MODUL

```
0. FONDASI                    ✅ SELESAI
1. AUTH & AKSES
   1.1 Login & Logout          ✅ SELESAI
   1.2 Lupa Password           ✅ SELESAI
   1.3 Ganti Password          🔲 Belum
   1.4 Role Management         🔲 Belum
   1.5 Permission Management   🔲 Belum
   1.6 Audit Trail             🔲 Belum
2. MASTER DATA                 🔲 Belum
3. ENTITAS UTAMA               🔲 Belum
4. USER MANAGEMENT             🔲 Belum
5. PROYEK                      🔲 Belum
6. OPERASIONAL                 🔲 Belum
7. LAPORAN & DASHBOARD         🔲 Belum
8. NOTIFIKASI                  🔲 Belum
```

---

## CATATAN TEKNIS GLOBAL

Ditemukan saat coding, berlaku untuk semua modul ke depan:

- **Ekstensi Livewire Volt** → disimpan sebagai `.blade.php`, bukan `.wire.php`. Penulisan `.wire.php` hanya ada di dokumen spesifikasi
- **Flux UI Pro directive** → gunakan `@fluxAppearance`, BUKAN `@fluxStyles`
- **`Fortify::ignoreRoutes()`** → wajib dipanggil di `register()` di `FortifyServiceProvider`, BUKAN `AppServiceProvider` dan BUKAN `boot()`
- **AuditTrail via tinker** → method `latest()` tidak bekerja karena tidak ada kolom `updated_at`. Gunakan `::count()` atau cek langsung di DBeaver
- **Layout auth** → `resources/views/components/layouts/auth.blade.php`
- **Password policy** → production: min 12 karakter + mixed case + angka + simbol. Development: null (tidak ada batasan). Diset di `AppServiceProvider`
- **MCP filesystem path** → gunakan `\\wsl.localhost\Ubuntu\...` bukan `/home/...` saat akses file WSL dari Claude Desktop
- **Livewire method vs property** → jangan beri nama method sama dengan nama property. Contoh: property `$login` + method `login()` = bentrok. Solusi: ganti nama method, misal `masuk()`
- **GitHub Actions** → workflow `tests.yml` & `lint.yml` sudah dinonaktifkan untuk branch `main` & `master`. Hanya aktif di branch `develop` & `workos`
- **Email** → `MAIL_MAILER=log` (development). Belum setup SMTP sungguhan. Rencana: Mailpit untuk development, Resend/Brevo untuk production saat sudah punya domain
- **Pembagian kerja** → perubahan kecil (1-3 baris) dikerjakan sendiri oleh user di VS Code. File baru / perubahan besar dikerjakan Claude. Setiap mau baca/tulis file, Claude konfirmasi dulu ke user

---

## DETAIL MODUL SELESAI

### Modul 1.1 — Login & Logout ✅

**Files yang dibuat/diubah:**
```
app/Models/User.php
app/Models/AuditTrail.php
app/Services/AuditTrailService.php
resources/views/livewire/auth/login.blade.php
resources/views/livewire/auth/logout.blade.php
resources/views/components/layouts/auth.blade.php
database/migrations/2026_03_14_234438_add_korporasi_status_to_users_table.php
database/migrations/2026_03_14_234850_create_audit_trails_table.php
routes/auth.php
```

**Keputusan arsitektur:**
- Fortify dipakai sebagai infrastruktur keamanan (token, rate limiting, dll)
- Livewire handle UI + logic bisnis (audit trail, cek status akun)
- `Fortify::ignoreRoutes()` dipanggil di `FortifyServiceProvider::register()`

**Data Super Admin:**
- Nama: `Ridho` | Email: `mhdreedho@gmail.com` | Username: `mhdreedho` | Password: `admin` | ID: `1`

---

### Modul 1.2 — Lupa Password ✅

**Files yang dibuat/diubah:**
```
routes/auth.php                                          ← uncomment routes forgot & reset password
resources/views/livewire/auth/forgot-password.blade.php ← baru
resources/views/livewire/auth/reset-password.blade.php  ← baru
app/Providers/FortifyServiceProvider.php                ← update rate limiter key
database/migrations/2026_03_15_000001_add_username_to_users_table.php ← baru
.github/workflows/tests.yml                             ← nonaktifkan trigger main & master
.github/workflows/lint.yml                              ← nonaktifkan trigger main & master
```

**Fitur yang diimplementasi:**
- Login bisa pakai **username ATAU email** (deteksi otomatis via `@`)
- Lupa password support input username atau email
- Jika input username → tampilkan email tersensor (contoh: `m*******o@gmail.com`)
- Jika input email → tampilkan email asli tanpa sensor
- Jika username/email tidak terdaftar → pesan error tegas (bukan ambigu)
- Token expired/sudah dipakai → halaman "Link Tidak Valid" tampil langsung saat buka URL
- Flash message sukses di halaman login setelah reset berhasil
- Notif gagal login tampil di atas form (bukan di bawah field input)

**Keputusan arsitektur:**
- Logic login sepenuhnya di `login.blade.php` (Livewire) — tidak pakai `Fortify::authenticateUsing()`
- Method login di Livewire diberi nama `masuk()` bukan `login()` — menghindari bentrok dengan property `$login`
- Email masih pakai `MAIL_MAILER=log` — link reset diambil dari log file saat development

---

## AGENDA SESI BERIKUTNYA

> ⚠️ Sesi berikutnya BUKAN lanjut modul 1.3 — tapi **benahi UI halaman auth** dulu:
> - Ganti placeholder logo "S" dengan logo/branding SISKO yang proper
> - Review tampilan halaman login, forgot-password, reset-password
> - Perbaikan UI lain yang diperlukan
> Setelah UI beres → lanjut modul 1.3 Ganti Password

---

## LOG PERUBAHAN

| Tanggal | Keterangan |
|---------|------------|
| Maret 2026 | Modul 0 Fondasi selesai |
| Maret 2026 | Modul 1.1 Login & Logout selesai |
| Maret 2026 | Pisahkan catatan implementasi ke SISKO-progress.md |
| Maret 2026 | Setup MCP filesystem — akses langsung ke file WSL dari Claude Desktop |
| Maret 2026 | Modul 1.2 Lupa Password selesai + fitur username login |
