# SISKO — Dokumen Development Detail

## Spesifikasi Teknis Lengkap per Modul (Versi C)

_Versi 1.1 — Maret 2026_

> **Dokumen ini adalah referensi teknis pribadi.**
> Berisi detail lengkap setiap modul: tabel database, model, komponen Livewire,
> routes, permission, business rules, dan edge cases.
> Gunakan ini sebagai blueprint saat coding atau saat lost tracking.

---

# MODUL 0 — FONDASI ✅ SELESAI

| Item              | Detail                                   |
| ----------------- | ---------------------------------------- |
| Laravel           | 12.x                                     |
| Livewire          | 4.x (Single-file components `.wire.php`) |
| Flux UI Pro       | 2.13.0 (local path via Composer)         |
| Spatie Permission | latest                                   |
| PostgreSQL        | 16.x                                     |
| Vite              | 7.x                                      |
| Tailwind CSS      | 4.x                                      |
| Pest              | latest                                   |
| Laravel Boost     | installed (Claude Code configured)       |

---

# MODUL 1 — AUTH & AKSES

## 1.1 Login & Logout ✅ SELESAI

**Tabel:** `users` (sudah ada dari migration Laravel default)

```
id, name, email, email_verified_at, password, remember_token,
created_at, updated_at, korporasi_id (tambahan), status (aktif/nonaktif)
```

**Files:**

```
app/Models/User.php
resources/views/livewire/auth/login.blade.php
resources/views/livewire/auth/logout.blade.php
resources/views/components/layouts/auth.blade.php
app/Models/AuditTrail.php
app/Services/AuditTrailService.php
database/migrations/2026_03_14_234438_add_korporasi_status_to_users_table.php
database/migrations/2026_03_14_234850_create_audit_trails_table.php
routes/auth.php
```

**Routes:**

```
GET  /login          → Auth/Login component
POST /login          → proses login
POST /logout         → proses logout
```

**Middleware:**

```
guest  → halaman login (redirect jika sudah login)
auth   → semua halaman yang butuh login
```

**Business Rules:**

- User dengan status `nonaktif` → tidak bisa login, tampil pesan "Akun Anda tidak aktif"
- Session expired → redirect ke login
- Remember me → session panjang (opsional, bisa diaktifkan)

**Edge Cases:**

- Email tidak terdaftar → pesan error generic (jangan bedakan email vs password salah, alasan keamanan)
- Password salah → pesan error generic
- Akun nonaktif → pesan spesifik "Akun tidak aktif"

**Audit Trail:** Catat setiap login berhasil & gagal (user_id/email, IP, timestamp, status)

### Catatan Implementasi

**Arsitektur Auth — Fortify + Livewire (Gabungan):**

- Fortify tetap dipakai untuk infrastruktur keamanan (token reset password, dll)
- Livewire handle tampilan + logic bisnis (login, audit trail, cek status akun)
- Keputusan ini diambil agar kontrol penuh atas logic login tanpa reinvent the wheel untuk keamanan

**Hal-hal teknis penting:**

- `Fortify::ignoreRoutes()` harus dipanggil di method `register()`, BUKAN `boot()` — karena Fortify mendaftarkan routes di `boot()`, jadi harus di-ignore sebelum itu
- Livewire Volt component disimpan sebagai `.blade.php` (bukan `.wire.php`) — ekstensi `.wire.php` hanya konvensi penulisan di dokumen ini
- `@fluxAppearance` adalah directive yang benar untuk Flux versi ini, BUKAN `@fluxStyles`
- Method `latest()` tidak bekerja untuk model `AuditTrail` via tinker karena tidak ada kolom `updated_at` — gunakan `::count()` atau lihat langsung di DBeaver
- Layout auth ada di `resources/views/components/layouts/auth.blade.php`

**Yang sengaja ditunda:**

- Link "Lupa password?" di halaman login sementara pakai `href="#"` — akan diisi dengan `route('password.request')` saat modul 1.2 selesai

**Data Super Admin (dibuat via tinker):**

- Nama: `Ridho`
- Email: `mhdreedho@gmail.com`
- Password: `admin`
- Status: `aktif`
- ID: `1`

---

## 1.2 Lupa Password

**Tabel:** `password_reset_tokens` (sudah ada dari migration Laravel default)

```
email, token, created_at
```

**Files:**

```
resources/views/livewire/auth/forgot-password.wire.php
resources/views/livewire/auth/reset-password.wire.php
app/Notifications/ResetPasswordNotification.php
```

**Routes:**

```
GET  /forgot-password          → form input email
POST /forgot-password          → kirim link reset
GET  /reset-password/{token}   → form reset password baru
POST /reset-password           → proses reset password
```

**Business Rules:**

- Token expired: 60 menit
- 1 email = 1 token aktif (token lama di-invalidate saat request baru)
- Email tidak terdaftar → tampil pesan sukses palsu (alasan keamanan, jangan konfirmasi email terdaftar/tidak)

**Edge Cases:**

- Token sudah expired → redirect ke forgot password dengan pesan error
- Token sudah dipakai → redirect ke forgot password dengan pesan error

---

## 1.3 Ganti Password

**Files:**

```
resources/views/livewire/profile/change-password.wire.php
```

**Business Rules:**

- Wajib input password lama (verifikasi dulu)
- Password baru minimal 8 karakter
- Password baru & konfirmasi harus sama
- Super Admin bisa force reset tanpa input password lama

**Hak Akses:**

- Semua user → ganti password sendiri
- Super Admin → force reset password user manapun

---

## 1.4 Role Management

**Package:** Spatie Laravel Permission

**Tabel (otomatis dari Spatie):**

```
roles                    → id, name, guard_name, created_at, updated_at
permissions              → id, name, guard_name, created_at, updated_at
model_has_roles          → role_id, model_type, model_id
role_has_permissions     → permission_id, role_id
model_has_permissions    → permission_id, model_type, model_id
```

**Tambahan kolom di tabel `roles`:**

```
is_default (boolean, default false) → untuk tandai 6 role default
```

**Files:**

```
app/Models/Role.php (extend Spatie Role)
resources/views/livewire/settings/roles/index.wire.php
resources/views/livewire/settings/roles/form.wire.php
resources/views/livewire/settings/roles/permissions.wire.php
database/seeders/RoleSeeder.php
```

**6 Role Default (di-seed, tidak bisa dihapus):**

```
1. Super Admin
2. Direktur Utama
3. Direktur Operasional
4. Sekretaris
5. Admin
6. Pengawas Lapangan
```

**Business Rules:**

- Role dengan `is_default = true` → tombol hapus tidak tampil, tidak bisa dihapus via API
- Role custom → bisa CRUD penuh oleh Super Admin
- Role yang sudah di-assign ke user → tidak bisa dihapus (cek dulu sebelum hapus)
- Semua delete → soft delete

**Permission Default (di-seed):**
Format: `{modul}.{aksi}` — contoh: `proyek.view`, `proyek.create`, `proyek.edit`, `proyek.delete`, `proyek.approve`

**Hak Akses:**

- Kelola role & permission: Super Admin only

---

## 1.5 Audit Trail

**Tabel:**

```
audit_trails
├── id
├── korporasi_id
├── user_id (nullable, null jika sistem)
├── aksi (create/update/delete/view/login/logout)
├── modul (nama modul: proyek, rap, pr, dll)
├── record_id (nullable, ID record yang diakses)
├── record_type (nullable, nama model)
├── perubahan (jsonb, nullable) → {field: {before: x, after: y}}
├── ip_address
├── user_agent
├── created_at
```

**Files:**

```
app/Models/AuditTrail.php
app/Services/AuditTrailService.php
app/Observers/AuditObserver.php (attach ke semua model utama)
resources/views/livewire/settings/audit-trail/index.wire.php
```

**Business Rules:**

- Semua aksi CRUD di semua modul otomatis dicatat via Observer
- Login/Logout dicatat manual di AuthController
- Kolom `perubahan` hanya diisi untuk aksi `update` (before & after per field)
- Read only — tidak ada delete audit trail

**Hak Akses:** Super Admin only

---

# MODUL 2 — MASTER DATA

> Aturan umum semua master data:
>
> - Input, Edit, Hapus: Super Admin, Dirut, Sekretaris
> - Semua delete → soft delete
> - Master data yang sudah terhubung ke tabel lain → tidak bisa dihapus (sistem cek otomatis)
> - Format file upload: PDF, JPG, PNG

## 2.1 Provinsi & Kota

**Tabel:**

```
provinsis
├── id, korporasi_id, nama, kode, deleted_at, timestamps

kotas
├── id, korporasi_id, provinsi_id, nama, kode, deleted_at, timestamps
```

**Files:**

```
app/Models/Provinsi.php
app/Models/Kota.php
database/seeders/ProvinsiKotaSeeder.php (data awal 38 provinsi Indonesia)
resources/views/livewire/master/provinsi/index.wire.php
resources/views/livewire/master/provinsi/form.wire.php
resources/views/livewire/master/kota/index.wire.php
resources/views/livewire/master/kota/form.wire.php
```

**Business Rules:**

- Provinsi dihapus → cek dulu apakah ada Kota yang pakai
- Kota dihapus → cek dulu apakah ada entitas lain yang pakai (Proyek, Perusahaan, dll)
- Hierarkis: Provinsi → Kota/Kabupaten

**Permission:**

```
provinsi.view, provinsi.create, provinsi.edit, provinsi.delete
kota.view, kota.create, kota.edit, kota.delete
```

---

## 2.2 Jenis Kontak

**Tabel:**

```
jenis_kontaks
├── id, korporasi_id, nama, icon (nullable), is_default (boolean)
├── deleted_at, timestamps
```

**Files:**

```
app/Models/JenisKontak.php
database/seeders/JenisKontakSeeder.php
resources/views/livewire/master/jenis-kontak/index.wire.php
resources/views/livewire/master/jenis-kontak/form.wire.php
```

**8 Default (di-seed, `is_default = true`):**

```
No. Telepon, No. HP / WhatsApp, Email, Alamat,
Koordinat GPS, Website, Facebook, Instagram
```

**Business Rules:**

- Jenis kontak default → bisa diedit nama/icon, tidak bisa dihapus
- Jenis kontak custom → bisa dihapus jika belum dipakai
- Jika sudah dipakai di entitas manapun → terkunci (tidak bisa dihapus)

**Permission:**

```
jenis-kontak.view, jenis-kontak.create, jenis-kontak.edit, jenis-kontak.delete
```

---

## 2.3 Bank

**Tabel:**

```
banks
├── id, korporasi_id, nama, singkatan, kode_bank
├── deleted_at, timestamps
```

**Files:**

```
app/Models/Bank.php
database/seeders/BankSeeder.php (bank-bank umum Indonesia)
resources/views/livewire/master/bank/index.wire.php
resources/views/livewire/master/bank/form.wire.php
```

**Business Rules:**

- Bank yang sudah dipakai di Master Rekening → tidak bisa dihapus

**Permission:**

```
bank.view, bank.create, bank.edit, bank.delete
```

---

## 2.4 Rekening

**Tabel:**

```
rekenings
├── id, korporasi_id
├── rekening_able_type (polymorphic: Korporasi/Perusahaan/PemberiKerja/Vendor/Investor/User)
├── rekening_able_id
├── bank_id (FK ke banks)
├── nomor_rekening, atas_nama, cabang (nullable)
├── jenis (giro/tabungan)
├── deleted_at, timestamps
```

**Files:**

```
app/Models/Rekening.php
resources/views/livewire/components/rekening-form.wire.php (reusable component)
```

**Business Rules:**

- Rekening adalah polymorphic → dipakai oleh semua entitas
- Satu entitas bisa punya banyak rekening

---

## 2.5 Klasifikasi Internal

**Tabel:**

```
klasifikasi_kategoris
├── id, korporasi_id, nama, urutan (nullable)
├── deleted_at, timestamps

klasifikasi_items
├── id, korporasi_id, kategori_id (FK)
├── nama, urutan (nullable)
├── deleted_at, timestamps
```

**Files:**

```
app/Models/KlasifikasiKategori.php
app/Models/KlasifikasiItem.php
database/seeders/KlasifikasiInternalSeeder.php
resources/views/livewire/master/klasifikasi/index.wire.php
resources/views/livewire/master/klasifikasi/form.wire.php
```

**Default Kategori & Item (di-seed):**

```
Pekerjaan Sipil
├── Pekerjaan Persiapan, Pekerjaan Tanah, Pekerjaan Pondasi
├── Pekerjaan Beton, Pekerjaan Besi & Baja
├── Pekerjaan Pasangan & Plesteran, Pekerjaan Atap
├── Pekerjaan Lantai, Pekerjaan Dinding & Partisi
├── Pekerjaan Pintu & Jendela, Pekerjaan Finishing

Pekerjaan Mekanikal & Elektrikal
├── Pekerjaan Listrik, Pekerjaan Plumbing
├── Pekerjaan AC & Ventilasi, Pekerjaan Sanitasi

Pekerjaan Luar
├── Pekerjaan Jalan & Parkir, Pekerjaan Drainase
├── Pekerjaan Taman & Lansekap, Pekerjaan Pagar & Gerbang

Pekerjaan Preventif & K3
├── Keselamatan Kerja (K3), Proteksi & Perlindungan
├── Pengujian & Quality Control

Tenaga Kerja
├── Mandor, Tukang, Pekerja Harian

Pengadaan Material
├── Material Bangunan, Material Mekanikal, Material Elektrikal

Subkontraktor
├── Subkon Sipil, Subkon Mekanikal, Subkon Elektrikal

Biaya Proyek Lainnya
├── Perizinan & Administrasi, Transportasi & Mobilisasi
├── Asuransi & Jaminan, Biaya Tak Terduga
```

**Business Rules:**

- Item yang sudah di-mapping ke item RAP → tidak bisa dihapus
- Kategori yang masih punya item aktif → tidak bisa dihapus

**Permission:**

```
klasifikasi.view, klasifikasi.create, klasifikasi.edit, klasifikasi.delete
```

---

# MODUL 3 — ENTITAS UTAMA

> Semua entitas menggunakan **polymorphic relations** untuk:
>
> - Kontak → `kontaks` table (kontakable_type, kontakable_id)
> - Rekening → `rekenings` table (rekening_able_type, rekening_able_id)
> - Dokumen → `dokumens` table (dokumenable_type, dokumenable_id)

**Tabel Kontak (shared):**

```
kontaks
├── id, korporasi_id
├── kontakable_type, kontakable_id (polymorphic)
├── jenis_kontak_id (FK ke jenis_kontaks)
├── nilai (isi kontak: nomor HP, email, URL, dll)
├── keterangan (nullable)
├── deleted_at, timestamps
```

**Tabel Dokumen (shared):**

```
dokumens
├── id, korporasi_id
├── dokumenable_type, dokumenable_id (polymorphic)
├── jenis_dokumen (nama jenis, string)
├── singkatan (nullable)
├── deleted_at, timestamps

dokumen_files
├── id, dokumen_id
├── nama_file, path, mime_type, ukuran
├── deleted_at, timestamps
```

---

## 3.1 Korporasi

**Tabel:**

```
korporasis
├── id, nama, slogan (nullable), logo (nullable), alamat
├── timestamps (NO deleted_at — hanya 1 data, tidak bisa dihapus)
```

**Files:**

```
app/Models/Korporasi.php
resources/views/livewire/settings/korporasi/edit.wire.php
```

**Business Rules:**

- Hanya 1 data korporasi (tidak bisa tambah atau hapus)
- Hanya Super Admin yang bisa edit
- Logo: format JPG/PNG, max size tertentu

**Permission:**

```
korporasi.view, korporasi.edit
```

---

## 3.2 Perusahaan (CV/PT)

**Tabel:**

```
perusahaans
├── id, korporasi_id, nama, jenis (cv/pt)
├── deleted_at, timestamps
```

**Jenis Dokumen Default (di-seed sebagai referensi):**

```
AKTA, SKPP, BPJSTK, NPWP, SPT, KSWP, KTPDIR, NPWPDIR,
SKDU, KTA, SBU, OSS RBA, SKK, KTPSKK, NPWPSKK
```

**Files:**

```
app/Models/Perusahaan.php
resources/views/livewire/master/perusahaan/index.wire.php
resources/views/livewire/master/perusahaan/form.wire.php
resources/views/livewire/master/perusahaan/show.wire.php
```

**Business Rules:**

- Perusahaan yang sudah dipakai di Proyek → tidak bisa dihapus
- Dokumen: bisa multiple entry, tiap entry bisa multiple file
- Format file: PDF, JPG, PNG

**Permission:**

```
perusahaan.view, perusahaan.create, perusahaan.edit, perusahaan.delete
```

---

## 3.3 Pemberi Kerja

**Tabel:**

```
pemberi_kerjas
├── id, korporasi_id, nama, jenis (pemerintah/swasta)
├── kota_id (FK ke kotas, nullable), alamat (nullable)
├── telepon (nullable), email (nullable)
├── deleted_at, timestamps

pemberi_kerja_pics (kontak PIC)
├── id, pemberi_kerja_id, nama_lengkap, jabatan
├── deleted_at, timestamps
```

**Files:**

```
app/Models/PemberiKerja.php
app/Models/PemberiKerjaPic.php
resources/views/livewire/master/pemberi-kerja/index.wire.php
resources/views/livewire/master/pemberi-kerja/form.wire.php
resources/views/livewire/master/pemberi-kerja/show.wire.php
```

**Business Rules:**

- Pemberi Kerja yang sudah dipakai di Proyek → tidak bisa dihapus
- PIC bisa punya multiple kontak (via tabel kontaks polymorphic)

**Permission:**

```
pemberi-kerja.view, pemberi-kerja.create, pemberi-kerja.edit, pemberi-kerja.delete
```

---

## 3.4 Vendor

**Tabel:**

```
vendors
├── id, korporasi_id, nama
├── jenis (jsonb array: supplier_material/jasa/subkontraktor) → bisa multiple
├── kota_id (FK ke kotas), alamat (nullable)
├── telepon (nullable), email (nullable), npwp (nullable)
├── deleted_at, timestamps

vendor_pics
├── id, vendor_id, nama_lengkap, jabatan
├── deleted_at, timestamps
```

**Files:**

```
app/Models/Vendor.php
app/Models/VendorPic.php
resources/views/livewire/master/vendor/index.wire.php
resources/views/livewire/master/vendor/form.wire.php
resources/views/livewire/master/vendor/show.wire.php
```

**Business Rules:**

- Vendor yang sudah dipakai di PR → tidak bisa dihapus
- Jenis vendor bisa multiple (satu vendor bisa supplier sekaligus subkon)

**Permission:**

```
vendor.view, vendor.create, vendor.edit, vendor.delete
```

---

## 3.5 Investor

**Tabel:**

```
investors
├── id, korporasi_id, nama, jenis (perorangan/perusahaan)
├── kota_id (nullable), alamat (nullable)
├── referral_user_id (FK ke users, nullable)
├── deleted_at, timestamps
```

**Files:**

```
app/Models/Investor.php
resources/views/livewire/master/investor/index.wire.php
resources/views/livewire/master/investor/form.wire.php
resources/views/livewire/master/investor/show.wire.php
```

**Permission:**

```
investor.view, investor.create, investor.edit, investor.delete
```

---

# MODUL 4 — USER MANAGEMENT

## 4.1 Profil User

**Tabel:**

```
profil_users
├── id, korporasi_id
├── nama_lengkap (wajib), nama_panggilan (nullable)
├── jenis_kelamin (wajib), tempat_lahir (nullable), tanggal_lahir (nullable)
├── agama (nullable), status_pernikahan (nullable)
├── kewarganegaraan (wni/wna, nullable), nik (nullable), npwp (nullable)
├── foto_profil (nullable)
├── status (aktif/nonaktif, wajib)
├── jabatan (wajib), departemen (nullable)
├── status_karyawan (tetap/kontrak/freelance/lainnya, wajib)
├── tanggal_bergabung (nullable), tanggal_berakhir_kontrak (nullable)
├── alamat_ktp (nullable), alamat_domisili (nullable), kota_id (nullable)
├── kontak_darurat_nama (nullable), kontak_darurat_hubungan (nullable)
├── kontak_darurat_hp (nullable)
├── deleted_at, timestamps
```

**Files:**

```
app/Models/ProfilUser.php
resources/views/livewire/users/profil/index.wire.php
resources/views/livewire/users/profil/form.wire.php
resources/views/livewire/users/profil/show.wire.php
```

**Business Rules:**

- 1 Profil User → maksimal 1 Akun User
- Nonaktifkan profil → otomatis nonaktifkan akun terkait
- Profil yang sudah terhubung ke data lain (investor referral, assign proyek, dll) → tidak bisa dihapus

**Hak Akses:**

```
Super Admin        → lihat semua, buat, edit semua profil
Dirut              → lihat semua
Role lain          → lihat profil di proyek ter-assign saja
Pemilik profil     → edit profil sendiri
```

**Permission:**

```
profil-user.view, profil-user.view-all, profil-user.create
profil-user.edit, profil-user.edit-own, profil-user.deactivate
```

---

## 4.2 Akun User

**Tabel:** `users` (Laravel default, sudah ada)

```
Tambahan kolom:
├── profil_user_id (FK ke profil_users, nullable)
├── status (aktif/nonaktif)
├── korporasi_id
```

**Files:**

```
resources/views/livewire/users/akun/form.wire.php
resources/views/livewire/users/akun/reset-password.wire.php
app/Services/AkunUserService.php
```

**Business Rules:**

- 1 Profil → maksimal 1 Akun (cek sebelum buat akun baru)
- Password bisa di-set manual atau auto-generate (random)
- Checkbox: kirim password via email atau tidak
- User bisa ganti password sendiri
- Super Admin bisa force reset tanpa tahu password lama
- Nonaktifkan akun → user tidak bisa login, data tetap ada

**Permission:**

```
akun-user.view, akun-user.create, akun-user.deactivate
akun-user.reset-password, akun-user.force-reset-password
```

---

# MODUL 5 — PROYEK

## 5.1 Data Proyek

**Tabel:**

```
proyeks
├── id, korporasi_id
├── nama (wajib), nomor_sppbj (nullable), nomor_kontrak (nullable)
├── tahun_anggaran (wajib)
├── perusahaan_id (FK, wajib), pemberi_kerja_id (FK, wajib)
├── nilai_kontrak (decimal, wajib)
├── tanggal_mulai (wajib), tanggal_selesai_kontrak (wajib)
├── tanggal_selesai_realisasi (nullable)
├── kota_id (FK, wajib), alamat (wajib), koordinat_gps (nullable)
├── keterangan (nullable)
├── status (draft/aktif/suspended/finish/under_review/closed)
├── approved_by (FK ke users, nullable), approved_at (nullable)
├── deleted_at, timestamps

proyek_tim (assign tim)
├── id, proyek_id, user_id, role_id
├── assigned_by (FK ke users)
├── assigned_at, deleted_at, timestamps
```

**Status Flow:**

```
draft → aktif → suspended → aktif
              → finish → under_review ↔ finish
                       → closed (FINAL)
```

**Files:**

```
app/Models/Proyek.php
app/Models/ProyekTim.php
app/Services/ProyekService.php
resources/views/livewire/proyek/index.wire.php
resources/views/livewire/proyek/create.wire.php      (inisiasi)
resources/views/livewire/proyek/edit.wire.php         (pelengkapan)
resources/views/livewire/proyek/show.wire.php
resources/views/livewire/proyek/status.wire.php       (ubah status)
resources/views/livewire/proyek/tim.wire.php          (assign tim)
```

**Business Rules:**

- Inisiasi & Pelengkapan: Dirut, Sekretaris
- Approve (Draft → Aktif): Dirut only → data proyek terkunci
- Proyek Aktif ke atas → data proyek tidak bisa diedit
- Proyek Suspended → semua aktivitas terkunci sampai kembali Aktif
- Proyek Finish → masih bisa input pemasukan, pengeluaran terkunci
- Proyek Closed → semua data terkunci total
- Super Admin → bisa ubah ke status apapun termasuk dari Closed

**Syarat Finish:**

```
1. Progress semua item RAP = 100%
2. Tidak ada PR pending approval
3. BAST sudah diupload
```

**Assign Tim:**

- Dir Ops: max 1 user per proyek
- Admin & Pengawas: boleh lebih dari 1
- User di-unassign dari proyek aktif → akses dicabut langsung

**Permission:**

```
proyek.view, proyek.view-all, proyek.create, proyek.edit
proyek.approve, proyek.change-status, proyek.delete
proyek.assign-tim, proyek.upload-bast
```

---

## 5.2 RAP (Rencana Anggaran Pelaksanaan)

**Tabel:**

```
raps
├── id, korporasi_id, proyek_id (FK)
├── status (draft/pending/approved/rejected)
├── submitted_by (FK ke users, nullable), submitted_at (nullable)
├── approved_by (FK ke users, nullable), approved_at (nullable)
├── rejected_by (FK ke users, nullable), rejected_at (nullable)
├── catatan_reject (nullable)
├── deleted_at, timestamps

rap_kategoris (hierarki unlimited level)
├── id, korporasi_id, rap_id (FK)
├── parent_id (FK self-referential, nullable → null = level 1)
├── nama, urutan
├── deleted_at, timestamps

rap_items
├── id, korporasi_id, rap_id (FK), kategori_id (FK ke rap_kategoris)
├── nama, satuan, volume (decimal), harga_satuan (decimal)
├── total (decimal, computed: volume × harga_satuan)
├── klasifikasi_item_id (FK ke klasifikasi_items)
├── urutan
├── deleted_at, timestamps
```

**Status Flow:**

```
draft → pending → approved (terkunci total)
               → rejected → draft (bisa edit & submit ulang)
```

**Files:**

```
app/Models/Rap.php
app/Models/RapKategori.php
app/Models/RapItem.php
app/Services/RapService.php
resources/views/livewire/proyek/rap/index.wire.php
resources/views/livewire/proyek/rap/form.wire.php     (hierarki builder)
resources/views/livewire/proyek/rap/approve.wire.php
```

**Business Rules:**

- RAP hanya bisa diinput setelah proyek Aktif
- Operasional (PR, Progress, dll) hanya bisa dimulai setelah RAP Approved
- Hierarki kategori unlimited level (recursive)
- Setiap item wajib di-mapping ke Klasifikasi Internal
- Total otomatis kalkulasi ke atas sampai total RAP
- RAP Approved → terkunci total, tidak ada yang bisa edit

**Hak Akses Lihat:**

```
Super Admin, Dirut, Sekretaris, Dir Ops ter-assign → lihat semua field
Admin, Pengawas ter-assign → lihat item & bobot saja (tanpa harga)
```

**Permission:**

```
rap.view, rap.view-limited, rap.create, rap.edit
rap.submit, rap.approve, rap.reject
```

---

## 5.3 Addendum

**Tabel:**

```
addendums
├── id, korporasi_id, proyek_id (FK)
├── nomor_addendum, tanggal
├── perubahan_nilai_kontrak (decimal, nullable)
├── perubahan_waktu (integer hari, nullable)
├── perubahan_lingkup (text, nullable)
├── dokumen_path (nullable)
├── deleted_at, timestamps

addendum_rap_items (perubahan item RAP)
├── id, addendum_id, rap_item_id (FK, nullable)
├── aksi (tambah/ubah/hapus)
├── data_baru (jsonb, nullable)
├── timestamps
```

**Files:**

```
app/Models/Addendum.php
app/Models/AddendumRapItem.php
resources/views/livewire/proyek/addendum/index.wire.php
resources/views/livewire/proyek/addendum/form.wire.php
```

**Business Rules:**

- Addendum hanya dibuat saat proyek status Aktif
- Tidak perlu approval
- Nilai kontrak di proyek otomatis update setelah addendum disimpan
- Perubahan RAP: input item yang berubah saja (bukan full RAP ulang)
- Terkunci jika proyek Closed

**Permission:**

```
addendum.view, addendum.create, addendum.edit, addendum.delete
```

---

# MODUL 6 — OPERASIONAL

## 6.1 Progress Lapangan

**Tabel:**

```
progres_lapangans
├── id, korporasi_id, proyek_id (FK), rap_item_id (FK)
├── tanggal
├── progres_fisik (decimal, 0-100) → input manual
├── catatan (nullable)
├── status (draft/submitted)
├── submitted_by (FK ke users), submitted_at
├── reviewed_by (FK ke users, nullable), reviewed_at (nullable)
├── catatan_review (nullable)
├── is_reviewed (boolean, default false)
├── deleted_at, timestamps

progres_fotos
├── id, progres_lapangan_id
├── path, nama_file, ukuran
├── timestamps
```

**Status Flow:**

```
draft → submitted
reviewed (flag is_reviewed = true) → terkunci total
```

**Files:**

```
app/Models/ProgresLapangan.php
app/Models/ProgressFoto.php
resources/views/livewire/proyek/progres/index.wire.php
resources/views/livewire/proyek/progres/form.wire.php
resources/views/livewire/proyek/progres/review.wire.php
```

**Business Rules:**

- Input per item RAP
- Foto wajib minimal 1 per entry progress
- Budget terpakai (%) → otomatis dari realisasi pengeluaran vs RAP (bukan input manual)
- Indikator on-track/tertinggal → perbandingan progres fisik vs budget terpakai
- Setelah direview → terkunci total

**Permission:**

```
progres.view, progres.create, progres.review
```

---

## 6.2 Purchase Request (PR)

**Tabel:**

```
purchase_requests
├── id, korporasi_id
├── tipe (proyek/korporasi)
├── proyek_id (FK, nullable → null jika korporasi)
├── nomor_pr (auto-generate)
├── tanggal
├── keterangan (nullable)
├── status (draft/pending/approved/rejected/done/cancelled)
├── submitted_by (FK ke users), submitted_at
├── approved_by (FK ke users, nullable), approved_at (nullable)
├── rejected_by (FK ke users, nullable), rejected_at (nullable)
├── catatan_reject (nullable)
├── cancelled_by (FK ke users, nullable), cancelled_at (nullable)
├── deleted_at, timestamps

pr_items
├── id, purchase_request_id
├── nama_item, satuan, volume (decimal), harga_satuan (decimal)
├── total (decimal, computed)
├── vendor_id (FK ke vendors, nullable)
├── keterangan (nullable)
├── deleted_at, timestamps

pr_penerimaan_barangs
├── id, purchase_request_id
├── tanggal_terima
├── catatan (nullable)
├── is_reviewed (boolean, default false)
├── reviewed_by (FK ke users, nullable), reviewed_at (nullable)
├── catatan_review (nullable)
├── timestamps

pr_penerimaan_files
├── id, pr_penerimaan_barang_id
├── path, nama_file, timestamps
```

**Status Flow:**

```
draft → pending → approved → done (terkunci total)
                → rejected (revisi = entry baru)
       approved → cancelled (final)
```

**Files:**

```
app/Models/PurchaseRequest.php
app/Models/PrItem.php
app/Models/PrPenerimaanBarang.php
app/Services/PurchaseRequestService.php
resources/views/livewire/pr/index.wire.php
resources/views/livewire/pr/form.wire.php
resources/views/livewire/pr/approve.wire.php
resources/views/livewire/pr/penerimaan.wire.php
```

**Business Rules:**

- Multi item dalam 1 PR
- PR yang ditolak → revisi dibuat sebagai entry baru (entry lama tetap sebagai record)
- PR Cancelled → final, tidak bisa direaktivasi
- Approval proyek → Dirut & Dir Ops proyek terkait (siapa duluan approve, selesai)
- Approval korporasi → Dirut only

**Permission:**

```
pr.view, pr.view-all, pr.create, pr.approve, pr.reject
pr.cancel, pr.edit, pr.delete, pr.penerimaan, pr.penerimaan-review
```

---

## 6.3 Petty Cash

**Tabel:**

```
petty_cashes
├── id, korporasi_id, user_id (FK, pemilik/inputer)
├── tipe (proyek/korporasi)
├── proyek_id (FK, nullable)
├── tanggal, keterangan
├── nominal (decimal)
├── status (draft/submitted)
├── is_reviewed (boolean, default false)
├── reviewed_by (FK ke users, nullable), reviewed_at (nullable)
├── catatan_review (nullable)
├── deleted_at, timestamps

petty_cash_files
├── id, petty_cash_id, path, nama_file, timestamps

petty_cash_limits
├── id, korporasi_id, user_id (FK)
├── max_saldo (decimal, 0 = unlimited)
├── max_minus (decimal, 0 = unlimited)
├── timestamps
```

**Status Flow:**

```
draft → submitted
reviewed (is_reviewed = true) → terkunci total
```

**Files:**

```
app/Models/PettyCash.php
app/Models/PettyCashLimit.php
app/Services/PettyCashService.php
resources/views/livewire/petty-cash/index.wire.php
resources/views/livewire/petty-cash/form.wire.php
resources/views/livewire/petty-cash/review.wire.php
resources/views/livewire/petty-cash/limit.wire.php
```

**Business Rules:**

- Tidak perlu approval, langsung catat
- Cek limit sebelum input: max_saldo & max_minus (0 = unlimited)
- Saldo bisa minus (ada batas max minus per user)
- Role custom: jika petty cash tidak diaktifkan → menu tidak tampil sama sekali
- Notifikasi otomatis setelah submit

**Permission:**

```
petty-cash.view, petty-cash.create, petty-cash.review
petty-cash.set-limit, petty-cash.edit, petty-cash.delete
```

---

## 6.4 Top-up Petty Cash

**Tabel:**

```
petty_cash_topups
├── id, korporasi_id, user_id (FK, requester)
├── nominal (decimal), keterangan (nullable)
├── status (pending/approved/rejected)
├── approved_by (FK ke users, nullable), approved_at (nullable)
├── rejected_by (FK ke users, nullable), rejected_at (nullable)
├── catatan (nullable)
├── timestamps
```

**Status Flow:**

```
pending → approved → saldo bertambah otomatis
        → rejected
pending (bisa dibatalkan oleh requester & Super Admin)
approved → terkunci, tidak bisa diedit/dihapus
```

**Files:**

```
app/Models/PettyCashTopup.php
resources/views/livewire/petty-cash/topup/index.wire.php
resources/views/livewire/petty-cash/topup/form.wire.php
resources/views/livewire/petty-cash/topup/approve.wire.php
```

**Business Rules:**

- Request: semua role yang punya fitur petty cash
- Approval: Dirut (default, tidak bisa dihapus), bisa ditambah Super Admin
- Siapa duluan approve → selesai
- Saldo bertambah setelah di-approve (bukan saat request)

**Permission:**

```
topup.view, topup.create, topup.approve, topup.reject, topup.cancel
```

---

## 6.5 Buku Kas Petty Cash

**Tabel:** Tidak ada tabel terpisah — data diambil dari:

- `petty_cash_topups` (untuk mutasi masuk)
- `petty_cashes` (untuk mutasi keluar)

**Files:**

```
app/Services/BukuKasService.php
resources/views/livewire/petty-cash/buku-kas/index.wire.php
```

**Business Rules:**

- Read only, tidak bisa diedit atau dihapus
- Terbentuk otomatis dari transaksi
- Formula saldo:
    ```
    Saldo Awal
    + Top-up (setiap top-up approved)
    - Pengeluaran (setiap petty cash submitted)
    = Saldo Akhir (realtime)
    ```
- Tiap baris: tanggal, jenis, keterangan, nominal, saldo berjalan

**Hak Akses:**

```
Super Admin, Dirut → semua user
Dir Ops            → user di proyek ter-assign
Pemilik            → milik sendiri
```

---

## 6.6 Pemasukan Proyek

**Tabel:**

```
pemasukan_proyeks
├── id, korporasi_id, proyek_id (FK)
├── tipe (uang_muka/termin/pembayaran_akhir/uang_retensi/pembayaran_penuh)
├── tanggal, nominal (decimal)
├── keterangan (nullable)
├── status (draft/submitted)
├── is_reviewed (boolean, default false)
├── reviewed_by (FK ke users, nullable), reviewed_at (nullable)
├── catatan_review (nullable)
├── submitted_by (FK ke users), submitted_at
├── deleted_at, timestamps

pemasukan_files
├── id, pemasukan_proyek_id, path, nama_file, timestamps
```

**5 Tipe Pemasukan:**

```
1. Uang Muka
2. Termin
3. Pembayaran Akhir
4. Uang Retensi
5. Pembayaran Penuh (pengadaan barang/jasa tanpa retensi)
```

**Status Flow:**

```
draft → submitted
reviewed (is_reviewed = true) → terkunci total
```

**Business Rules:**

- Semua tipe opsional (tidak harus semua ada di setiap proyek)
- Nilai dicatat sebagai nominal
- Sistem otomatis hitung sisa dari nilai kontrak
- Proyek Finish → masih bisa input pemasukan
- Proyek Closed → tidak bisa input

**Permission:**

```
pemasukan.view, pemasukan.create, pemasukan.edit
pemasukan.delete, pemasukan.review
```

---

# MODUL 7 — LAPORAN & DASHBOARD

## 7.1 Dashboard per Role

**Files:**

```
resources/views/livewire/dashboard/super-admin.wire.php
resources/views/livewire/dashboard/dirut.wire.php
resources/views/livewire/dashboard/dir-ops.wire.php
resources/views/livewire/dashboard/sekretaris.wire.php
resources/views/livewire/dashboard/admin.wire.php
resources/views/livewire/dashboard/pengawas.wire.php
app/Services/DashboardService.php
```

**Widget per Role:**

| Widget                                  | Super Admin | Dirut | Dir Ops | Sekretaris | Admin | Pengawas |
| --------------------------------------- | :---------: | :---: | :-----: | :--------: | :---: | :------: |
| Info sistem                             |     ✅      |   -   |    -    |     -      |   -   |    -     |
| Ringkasan keuangan korporasi            |     ✅      |  ✅   |    -    |     -      |   -   |    -     |
| Daftar proyek aktif + status + progress |     ✅      |  ✅   |  ✅\*   |     ✅     | ✅\*  |   ✅\*   |
| PR pending approval                     |     ✅      |  ✅   |  ✅\*   |     -      |   -   |    -     |
| Top-up pending approval                 |     ✅      |  ✅   |    -    |     -      |   -   |    -     |
| RAP belum di-approve                    |     ✅      |   -   |    -    |     ✅     |   -   |    -     |
| PR & Petty Cash baru diinput            |     ✅      |   -   |    -    |     ✅     | ✅\*  |   ✅\*   |
| Progress belum direview                 |     ✅      |   -   |  ✅\*   |     -      |   -   |    -     |
| Progress input terakhir                 |     ✅      |   -   |    -    |     -      |   -   |   ✅\*   |

\*) hanya proyek ter-assign

**Business Rules:**

- Dashboard bisa filter per periode
- Dashboard Admin & Pengawas → tidak menampilkan nilai keuangan apapun
- Tidak ada kustomisasi widget (V1)

---

## 7.2 Laporan Proyek

**Files:**

```
resources/views/livewire/laporan/proyek/keuangan.wire.php
resources/views/livewire/laporan/proyek/progress.wire.php
resources/views/livewire/laporan/proyek/pengeluaran.wire.php
resources/views/livewire/laporan/proyek/pemasukan.wire.php
app/Services/LaporanProyekService.php
```

**Filter default:** periode, CV/PT, status proyek, Pemberi Kerja

**Export:** PDF (V1), Excel menyusul

**Permission:**

```
laporan.proyek-keuangan, laporan.proyek-progress
laporan.proyek-pengeluaran, laporan.proyek-pemasukan
```

---

## 7.3 Laporan Keuangan Korporasi

**Files:**

```
resources/views/livewire/laporan/korporasi/laba-rugi-cv.wire.php
resources/views/livewire/laporan/korporasi/laba-rugi-korporasi.wire.php
resources/views/livewire/laporan/korporasi/pengeluaran-korporasi.wire.php
resources/views/livewire/laporan/korporasi/arus-kas.wire.php
app/Services/LaporanKorporasiService.php
```

**Formula Laba:**

```
Laba Kotor per Proyek    = Pemasukan Proyek - Pengeluaran Proyek
Laba Kotor per CV/PT     = Total Laba Kotor semua proyek CV/PT
Laba Bersih Korporasi    = Total Laba Kotor semua CV/PT - Pengeluaran Korporasi
```

**Filter Arus Kas:**

```
Periode (dari - sampai)
Jenis transaksi (Semua/Pemasukan/Pengeluaran)
Sumber (Semua/per Proyek/Korporasi)
CV/PT
```

**Permission:**

```
laporan.laba-rugi-cv, laporan.laba-rugi-korporasi
laporan.pengeluaran-korporasi, laporan.arus-kas
```

---

## 7.4 Laporan Operasional

**Files:**

```
resources/views/livewire/laporan/operasional/rekap-proyek.wire.php
resources/views/livewire/laporan/operasional/rekap-pr.wire.php
resources/views/livewire/laporan/operasional/rekap-petty-cash.wire.php
app/Services/LaporanOperasionalService.php
```

**Permission:**

```
laporan.rekap-proyek, laporan.rekap-pr, laporan.rekap-petty-cash
```

---

# MODUL 8 — NOTIFIKASI

**Tabel:**

```
notifikasis
├── id, korporasi_id, user_id (FK, penerima)
├── judul, pesan (text)
├── tipe (pr_submit/pr_approve/pr_reject/topup_request/dll)
├── data (jsonb, nullable) → link ke record terkait
├── is_read (boolean, default false)
├── read_at (nullable)
├── timestamps
```

**Tabel Setelan Notifikasi:**

```
notifikasi_setelans
├── id, korporasi_id
├── event (nama event)
├── penerima_roles (jsonb array of role_id)
├── penerima_users (jsonb array of user_id, nullable)
├── is_active (boolean)
├── timestamps
```

**Files:**

```
app/Models/Notifikasi.php
app/Models/NotifikasiSetelan.php
app/Services/NotifikasiService.php
database/seeders/NotifikasiSetelanSeeder.php
resources/views/livewire/notifikasi/bell.wire.php     (bell icon + badge)
resources/views/livewire/notifikasi/list.wire.php     (dropdown list)
resources/views/livewire/settings/notifikasi/index.wire.php (setelan)
```

**Default Events (di-seed):**

```
pr_submit          → Dirut, Dir Ops proyek terkait
pr_approve         → Pembuat PR
pr_reject          → Pembuat PR
pr_cancel          → Pembuat PR
petty_cash_proyek  → Dirut, Dir Ops proyek terkait
petty_cash_korpor  → Dirut, Sekretaris
topup_request      → Dirut
topup_approve      → Requester
topup_reject       → Requester
progres_input      → Dir Ops, Dirut proyek terkait
rap_submit         → Dirut
rap_approve        → Pembuat RAP
rap_reject         → Pembuat RAP
status_proyek      → Tim proyek terkait
assign_tim         → User yang di-assign
```

**Business Rules:**

- Notifikasi ditampilkan di dalam sistem (bell icon) dengan badge counter angka
- User bisa tandai sudah dibaca
- Tidak bisa hapus notifikasi
- Fully configurable oleh Super Admin
- Tidak mempengaruhi alur sistem apapun
- V1: dalam sistem saja. Next: kirim via WhatsApp

**Permission:**

```
notifikasi.view, notifikasi.mark-read
notifikasi.settings (Super Admin only)
```

---

# CATATAN ARSITEKTUR

## Multi-tenancy

- Semua tabel utama memiliki kolom `korporasi_id`
- Semua query otomatis di-scope per `korporasi_id` via Global Scope
- Siap untuk SaaS V2

## Soft Delete

- Semua delete → soft delete (kolom `deleted_at`)
- Kecuali **draft** → hard delete
- Data terkunci (reviewed/approved/closed) → tidak bisa diedit atau dihapus

## File Upload

- Format: PDF, JPG, PNG
- Storage: `storage/app/private/` (local disk Laravel)
- Download bundle PDF: pilih semua atau sebagian, urutan bisa diatur

## Naming Convention

```
Models         → PascalCase singular   (ProyekTim, RapItem)
Tables         → snake_case plural     (proyek_tims, rap_items)
Livewire       → kebab-case path       (proyek/rap/form.wire.php)
Permissions    → kebab-case.aksi       (proyek.view, rap.approve)
Services       → PascalCase + Service  (ProyekService, RapService)
Seeders        → PascalCase + Seeder   (RoleSeeder, KlasifikasiSeeder)
```

## Service Layer

- Business logic → `app/Services/`
- Livewire component → hanya handle UI & memanggil Service
- Tidak pakai Repository Pattern (over-engineering untuk skala ini)

---

_— SISKO-Development-detail.md v1.1 —_
