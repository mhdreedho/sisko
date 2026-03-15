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
   1.2 Lupa Password           🔲 BERIKUTNYA
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
- **`Fortify::ignoreRoutes()`** → wajib dipanggil di `register()`, BUKAN `boot()`. Fortify mendaftarkan routes-nya di `boot()`, jadi harus di-ignore sebelum itu
- **AuditTrail via tinker** → method `latest()` tidak bekerja karena tidak ada kolom `updated_at`. Gunakan `::count()` atau cek langsung di DBeaver
- **Layout auth** → `resources/views/components/layouts/auth.blade.php`
- **Password policy** → production: min 12 karakter + mixed case + angka + simbol. Development: null (tidak ada batasan). Diset di `AppServiceProvider`
- **MCP filesystem path** → gunakan `\\wsl.localhost\Ubuntu\...` bukan `/home/...` saat akses file WSL dari Claude Desktop

---

## DETAIL MODUL SELESAI

### Modul 1.1 — Login & Logout ✅

**Files yang dibuat:**

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
- `Fortify::ignoreRoutes()` dipanggil di `AppServiceProvider::register()`

**Data Super Admin:**
- Nama: `Ridho` | Email: `mhdreedho@gmail.com` | Password: `admin` | ID: `1`

**Yang ditunda ke modul berikutnya:**
- Link "Lupa password?" masih `href="#"` → akan diisi `route('password.request')` saat 1.2 selesai
- `AuditTrail` model & service sudah ada, belum full dipakai → akan diperluas di modul 1.6

---

## LANGKAH BERIKUTNYA — Modul 1.2 Lupa Password

Urutan pengerjaan:
1. Tambah `Fortify::ignoreRoutes()` di `AppServiceProvider::register()`
2. Uncomment placeholder routes di `routes/auth.php`
3. Update link "Lupa password?" di login → `route('password.request')`
4. Buat Livewire component `auth.forgot-password`
5. Buat Livewire component `auth.reset-password`
6. Buat `app/Notifications/ResetPasswordNotification.php`
7. Override `sendPasswordResetNotification()` di `User` model
8. Test end-to-end
9. Commit

**Catatan penting:**
- `Features::resetPasswords()` di `config/fortify.php` sudah aktif ✅
- WAJIB tambah `ignoreRoutes()` agar routes Fortify tidak bentrok dengan routes Livewire

---

## LOG PERUBAHAN

| Tanggal | Keterangan |
|---------|------------|
| Maret 2026 | Modul 0 Fondasi selesai |
| Maret 2026 | Modul 1.1 Login & Logout selesai |
| Maret 2026 | Pisahkan catatan implementasi ke SISKO-progress.md |
| Maret 2026 | Setup MCP filesystem — akses langsung ke file WSL dari Claude Desktop |
