# SISKO — Dokumen Development Detail

## Spesifikasi Teknis Lengkap per Modul

_Versi 1.2 — Maret 2026_

> **Dokumen ini adalah referensi teknis blueprint per modul.**
> Berisi: tabel database, model, komponen Livewire, routes, permission, business rules, edge cases.
> Gunakan sebagai acuan saat coding atau saat butuh re-orientasi.
>
> **Status progres & catatan implementasi** → lihat `SISKO-progress.md` (di lokal WSL, baca via MCP filesystem).

---

# MODUL 0 — FONDASI

| Item              | Detail                                    |
| ----------------- | ----------------------------------------- |
| Laravel           | 12.x                                      |
| Livewire          | 4.x (Single-file components `.blade.php`) |
| Flux UI Pro       | 2.13.0 (local path via Composer)          |
| Spatie Permission | latest                                    |
| PostgreSQL        | 16.x                                      |
| Vite              | 7.x                                       |
| Tailwind CSS      | 4.x                                       |
| Pest              | latest                                    |
| Laravel Boost     | installed (Claude Code configured)        |

---

# MODUL 1 — AUTH & AKSES

## 1.1 Login & Logout

**Tabel:** `users` (sudah ada dari migration Laravel default)

```
id, name, email, email_verified_at, password, remember_token,
created_at, updated_at, korporasi_id (tambahan), status (aktif/nonaktif)
```

**Files:**

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

**Routes:**

```
GET  /login   → Auth/Login component
POST /login   → proses login
POST /logout  → proses logout
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

---

## 1.2 Lupa Password

**Tabel:** `password_reset_tokens` (sudah ada dari migration Laravel default)

```
email, token, created_at
```

**Files:**

```
resources/views/livewire/auth/forgot-password.blade.php
resources/views/livewire/auth/reset-password.blade.php
app/Notifications/ResetPasswordNotification.php
```

**Routes:**

```
GET  /forgot-password          → form input email
POST /forgot-password          → kirim link reset
GET  /reset-password/{token}   → form reset password baru
POST /reset-password           → proses reset password
```

**Middleware:** `guest` untuk semua route lupa password

**Business Rules:**

- Token expired: 60 menit (config `auth.passwords.users.expire`)
- 1 email = 1 token aktif (token lama di-invalidate saat request baru — default PasswordBroker)
- Email tidak terdaftar → tampil pesan sukses palsu (keamanan: jangan konfirmasi email terdaftar/tidak)

**Edge Cases:**

- Token sudah expired → redirect ke forgot-password + pesan error
- Token sudah dipakai → redirect ke forgot-password + pesan error

---

## 1.3 Ganti Password

**Files:**

```
resources/views/livewire/profile/change-password.blade.php
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
roles                 → id, name, guard_name, created_at, updated_at
permissions           → id, name, guard_name, created_at, updated_at
model_has_roles       → role_id, model_type, model_id
role_has_permissions  → permission_id, role_id
model_has_permissions → permission_id, model_type, model_id
```

**Tambahan kolom di tabel `roles`:**

```
is_default (boolean, default false) → untuk tandai 6 role default
```

**Files:**

```
app/Models/Role.php (extend Spatie Role)
resources/views/livewire/settings/roles/index.blade.php
resources/views/livewire/settings/roles/form.blade.php
resources/views/livewire/settings/roles/permissions.blade.php
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

**Hak Akses:** Kelola role & permission: Super Admin only

**Permission:**

```
role.view, role.create, role.edit, role.delete
permission.view, permission.assign
```

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

**Catatan:** Tidak ada kolom `updated_at` — audit trail tidak pernah diupdate.

**Files:**

```
app/Models/AuditTrail.php               ← sudah dibuat di modul 1.1
app/Services/AuditTrailService.php      ← sudah dibuat di modul 1.1
app/Observers/AuditObserver.php         ← dibuat di modul ini
resources/views/livewire/settings/audit-trail/index.blade.php
```

**Business Rules:**

- Semua aksi CRUD di semua modul otomatis dicatat via Observer
- Login/Logout dicatat manual via AuditTrailService
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
resources/views/livewire/master/provinsi/index.blade.php
resources/views/livewire/master/provinsi/form.blade.php
resources/views/livewire/master/kota/index.blade.php
resources/views/livewire/master/kota/form.blade.php
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
resources/views/livewire/master/jenis-kontak/index.blade.php
resources/views/livewire/master/jenis-kontak/form.blade.php
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
resources/views/livewire/master/bank/index.blade.php
resources/views/livewire/master/bank/form.blade.php
```

**Business Rules:** Bank yang sudah dipakai di Master Rekening → tidak bisa dihapus

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
resources/views/livewire/components/rekening-form.blade.php (reusable component)
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
resources/views/livewire/master/klasifikasi/index.blade.php
resources/views/livewire/master/klasifikasi/form.blade.php
```

**Default Kategori & Item (di-seed):** Lihat SISKO-analisis.md section 5.11

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
resources/views/livewire/settings/korporasi/edit.blade.php
```

**Business Rules:**

- Hanya 1 data korporasi (tidak bisa tambah atau hapus)
- Hanya Super Admin yang bisa edit
- Logo: format JPG/PNG

**Permission:** `korporasi.view, korporasi.edit`

---

## 3.2 Perusahaan (CV/PT)

**Tabel:**

```
perusahaans
├── id, korporasi_id, nama, jenis (cv/pt)
├── deleted_at, timestamps
```

**Files:**

```
app/Models/Perusahaan.php
resources/views/livewire/master/perusahaan/index.blade.php
resources/views/livewire/master/perusahaan/form.blade.php
resources/views/livewire/master/perusahaan/show.blade.php
```

**Business Rules:**

- Perusahaan yang sudah dipakai di Proyek → tidak bisa dihapus
- Dokumen: bisa multiple entry, tiap entry bisa multiple file
- Jenis dokumen default (seed): AKTA, SKPP, BPJSTK, NPWP, SPT, KSWP, KTPDIR, NPWPDIR, SKDU, KTA, SBU, OSS RBA, SKK, KTPSKK, NPWPSKK

**Permission:** `perusahaan.view, perusahaan.create, perusahaan.edit, perusahaan.delete`

---

## 3.3 Pemberi Kerja

**Tabel:**

```
pemberi_kerjas
├── id, korporasi_id, nama, jenis (pemerintah/swasta)
├── kota_id (FK ke kotas, nullable), alamat (nullable)
├── telepon (nullable), email (nullable)
├── deleted_at, timestamps

pemberi_kerja_pics
├── id, pemberi_kerja_id, nama_lengkap, jabatan
├── deleted_at, timestamps
```

**Files:**

```
app/Models/PemberiKerja.php
app/Models/PemberiKerjaPic.php
resources/views/livewire/master/pemberi-kerja/index.blade.php
resources/views/livewire/master/pemberi-kerja/form.blade.php
resources/views/livewire/master/pemberi-kerja/show.blade.php
```

**Business Rules:**

- Pemberi Kerja yang sudah dipakai di Proyek → tidak bisa dihapus
- PIC bisa punya multiple kontak (via tabel kontaks polymorphic)

**Permission:** `pemberi-kerja.view, pemberi-kerja.create, pemberi-kerja.edit, pemberi-kerja.delete`

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
resources/views/livewire/master/vendor/index.blade.php
resources/views/livewire/master/vendor/form.blade.php
resources/views/livewire/master/vendor/show.blade.php
```

**Business Rules:**

- Vendor yang sudah dipakai di PR → tidak bisa dihapus
- Jenis vendor bisa multiple (satu vendor bisa supplier sekaligus subkon)

**Permission:** `vendor.view, vendor.create, vendor.edit, vendor.delete`

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
resources/views/livewire/master/investor/index.blade.php
resources/views/livewire/master/investor/form.blade.php
resources/views/livewire/master/investor/show.blade.php
```

**Permission:** `investor.view, investor.create, investor.edit, investor.delete`

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
resources/views/livewire/users/profil/index.blade.php
resources/views/livewire/users/profil/form.blade.php
resources/views/livewire/users/profil/show.blade.php
```

**Business Rules:**

- 1 Profil User → maksimal 1 Akun User
- Nonaktifkan profil → otomatis nonaktifkan akun terkait
- Profil yang sudah terhubung ke data lain → tidak bisa dihapus

**Hak Akses:**

```
Super Admin    → lihat semua, buat, edit semua profil
Dirut          → lihat semua
Role lain      → lihat profil di proyek ter-assign saja
Pemilik profil → edit profil sendiri
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
resources/views/livewire/users/akun/form.blade.php
resources/views/livewire/users/akun/reset-password.blade.php
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

proyek_tim
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
resources/views/livewire/proyek/index.blade.php
resources/views/livewire/proyek/create.blade.php
resources/views/livewire/proyek/edit.blade.php
resources/views/livewire/proyek/show.blade.php
resources/views/livewire/proyek/status.blade.php
resources/views/livewire/proyek/tim.blade.php
```

**Business Rules:**

- Approve (Draft → Aktif): Dirut only → data proyek terkunci
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
├── submitted_by (FK), submitted_at
├── approved_by (FK), approved_at
├── rejected_by (FK), rejected_at
├── catatan_reject (nullable)
├── deleted_at, timestamps

rap_kategoris
├── id, korporasi_id, rap_id (FK)
├── parent_id (self-referential, nullable → null = level 1)
├── nama, urutan, deleted_at, timestamps

rap_items
├── id, korporasi_id, rap_id (FK), kategori_id (FK)
├── nama, satuan, volume (decimal), harga_satuan (decimal)
├── total (decimal, computed: volume × harga_satuan)
├── klasifikasi_item_id (FK ke klasifikasi_items)
├── urutan, deleted_at, timestamps
```

**Status Flow:**

```
draft → pending → approved (terkunci total)
               → rejected → draft
```

**Files:**

```
app/Models/Rap.php
app/Models/RapKategori.php
app/Models/RapItem.php
app/Services/RapService.php
resources/views/livewire/proyek/rap/index.blade.php
resources/views/livewire/proyek/rap/form.blade.php
resources/views/livewire/proyek/rap/approve.blade.php
```

**Business Rules:**

- RAP hanya bisa diinput setelah proyek Aktif
- Operasional hanya bisa dimulai setelah RAP Approved
- Hierarki kategori unlimited level (recursive)
- Setiap item wajib di-mapping ke Klasifikasi Internal
- RAP Approved → terkunci total

**Hak Akses Lihat:**

```
Super Admin, Dirut, Sekretaris, Dir Ops ter-assign → semua field
Admin, Pengawas ter-assign → item & bobot saja (tanpa harga)
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

addendum_rap_items
├── id, addendum_id, rap_item_id (FK, nullable)
├── aksi (tambah/ubah/hapus)
├── data_baru (jsonb, nullable)
├── timestamps
```

**Files:**

```
app/Models/Addendum.php
app/Models/AddendumRapItem.php
resources/views/livewire/proyek/addendum/index.blade.php
resources/views/livewire/proyek/addendum/form.blade.php
```

**Business Rules:**

- Hanya dibuat saat proyek Aktif, tidak perlu approval
- Nilai kontrak proyek otomatis update setelah addendum disimpan
- Terkunci jika proyek Closed

**Permission:** `addendum.view, addendum.create, addendum.edit, addendum.delete`

---

# MODUL 6 — OPERASIONAL

## 6.1 Progress Lapangan

**Tabel:**

```
progres_lapangans
├── id, korporasi_id, proyek_id (FK), rap_item_id (FK)
├── tanggal, progres_fisik (decimal, 0-100), catatan (nullable)
├── status (draft/submitted)
├── submitted_by (FK), submitted_at
├── reviewed_by (FK, nullable), reviewed_at (nullable)
├── catatan_review (nullable), is_reviewed (boolean, default false)
├── deleted_at, timestamps

progres_fotos
├── id, progres_lapangan_id, path, nama_file, ukuran, timestamps
```

**Files:**

```
app/Models/ProgresLapangan.php
app/Models/ProgressFoto.php
resources/views/livewire/proyek/progres/index.blade.php
resources/views/livewire/proyek/progres/form.blade.php
resources/views/livewire/proyek/progres/review.blade.php
```

**Business Rules:**

- Foto wajib minimal 1 per entry
- Budget terpakai (%) → otomatis dari realisasi pengeluaran vs RAP
- Setelah direview → terkunci total

**Permission:** `progres.view, progres.create, progres.review`

---

## 6.2 Purchase Request (PR)

**Tabel:**

```
purchase_requests
├── id, korporasi_id, tipe (proyek/korporasi)
├── proyek_id (FK, nullable), nomor_pr (auto-generate), tanggal
├── keterangan (nullable)
├── status (draft/pending/approved/rejected/done/cancelled)
├── submitted_by (FK), submitted_at
├── approved_by (FK, nullable), approved_at (nullable)
├── rejected_by (FK, nullable), rejected_at (nullable), catatan_reject (nullable)
├── cancelled_by (FK, nullable), cancelled_at (nullable)
├── deleted_at, timestamps

pr_items
├── id, purchase_request_id
├── nama_item, satuan, volume (decimal), harga_satuan (decimal)
├── total (decimal, computed), vendor_id (FK, nullable)
├── keterangan (nullable), deleted_at, timestamps

pr_penerimaan_barangs
├── id, purchase_request_id, tanggal_terima, catatan (nullable)
├── is_reviewed (boolean), reviewed_by (FK, nullable), reviewed_at (nullable)
├── catatan_review (nullable), timestamps

pr_penerimaan_files
├── id, pr_penerimaan_barang_id, path, nama_file, timestamps
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
resources/views/livewire/pr/index.blade.php
resources/views/livewire/pr/form.blade.php
resources/views/livewire/pr/approve.blade.php
resources/views/livewire/pr/penerimaan.blade.php
```

**Business Rules:**

- PR ditolak → revisi = entry baru (entry lama tetap sebagai record)
- Approval proyek → Dirut & Dir Ops proyek terkait (siapa duluan, selesai)
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
├── id, korporasi_id, user_id (FK), tipe (proyek/korporasi)
├── proyek_id (FK, nullable), tanggal, keterangan, nominal (decimal)
├── status (draft/submitted), is_reviewed (boolean)
├── reviewed_by (FK, nullable), reviewed_at (nullable)
├── catatan_review (nullable), deleted_at, timestamps

petty_cash_files
├── id, petty_cash_id, path, nama_file, timestamps

petty_cash_limits
├── id, korporasi_id, user_id (FK)
├── max_saldo (decimal, 0 = unlimited)
├── max_minus (decimal, 0 = unlimited)
├── timestamps
```

**Files:**

```
app/Models/PettyCash.php
app/Models/PettyCashLimit.php
app/Services/PettyCashService.php
resources/views/livewire/petty-cash/index.blade.php
resources/views/livewire/petty-cash/form.blade.php
resources/views/livewire/petty-cash/review.blade.php
resources/views/livewire/petty-cash/limit.blade.php
```

**Business Rules:**

- Tidak perlu approval, langsung catat
- Cek limit sebelum input (0 = unlimited)
- Role custom: jika petty cash tidak diaktifkan → menu tidak tampil

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
├── id, korporasi_id, user_id (FK)
├── nominal (decimal), keterangan (nullable)
├── status (pending/approved/rejected)
├── approved_by (FK, nullable), approved_at (nullable)
├── rejected_by (FK, nullable), rejected_at (nullable)
├── catatan (nullable), timestamps
```

**Files:**

```
app/Models/PettyCashTopup.php
resources/views/livewire/petty-cash/topup/index.blade.php
resources/views/livewire/petty-cash/topup/form.blade.php
resources/views/livewire/petty-cash/topup/approve.blade.php
```

**Business Rules:**

- Saldo bertambah setelah di-approve (bukan saat request)
- Siapa duluan approve → selesai

**Permission:** `topup.view, topup.create, topup.approve, topup.reject, topup.cancel`

---

## 6.5 Buku Kas Petty Cash

**Tabel:** Tidak ada tabel terpisah — data dari `petty_cash_topups` + `petty_cashes`

**Files:**

```
app/Services/BukuKasService.php
resources/views/livewire/petty-cash/buku-kas/index.blade.php
```

**Formula:**

```
Saldo Awal + Top-up (approved) - Pengeluaran (submitted) = Saldo Akhir
```

---

## 6.6 Pemasukan Proyek

**Tabel:**

```
pemasukan_proyeks
├── id, korporasi_id, proyek_id (FK)
├── tipe (uang_muka/termin/pembayaran_akhir/uang_retensi/pembayaran_penuh)
├── tanggal, nominal (decimal), keterangan (nullable)
├── status (draft/submitted), is_reviewed (boolean)
├── reviewed_by (FK, nullable), reviewed_at (nullable)
├── catatan_review (nullable)
├── submitted_by (FK), submitted_at
├── deleted_at, timestamps

pemasukan_files
├── id, pemasukan_proyek_id, path, nama_file, timestamps
```

**Business Rules:**

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
resources/views/livewire/dashboard/super-admin.blade.php
resources/views/livewire/dashboard/dirut.blade.php
resources/views/livewire/dashboard/dir-ops.blade.php
resources/views/livewire/dashboard/sekretaris.blade.php
resources/views/livewire/dashboard/admin.blade.php
resources/views/livewire/dashboard/pengawas.blade.php
app/Services/DashboardService.php
```

**Business Rules:**

- Dashboard bisa filter per periode
- Dashboard Admin & Pengawas → tidak menampilkan nilai keuangan
- Tidak ada kustomisasi widget (V1)

---

## 7.2 Laporan Proyek

**Files:**

```
resources/views/livewire/laporan/proyek/keuangan.blade.php
resources/views/livewire/laporan/proyek/progress.blade.php
resources/views/livewire/laporan/proyek/pengeluaran.blade.php
resources/views/livewire/laporan/proyek/pemasukan.blade.php
app/Services/LaporanProyekService.php
```

**Permission:**

```
laporan.proyek-keuangan, laporan.proyek-progress
laporan.proyek-pengeluaran, laporan.proyek-pemasukan
```

---

## 7.3 Laporan Keuangan Korporasi

**Files:**

```
resources/views/livewire/laporan/korporasi/laba-rugi-cv.blade.php
resources/views/livewire/laporan/korporasi/laba-rugi-korporasi.blade.php
resources/views/livewire/laporan/korporasi/pengeluaran-korporasi.blade.php
resources/views/livewire/laporan/korporasi/arus-kas.blade.php
app/Services/LaporanKorporasiService.php
```

**Formula Laba:**

```
Laba Kotor per Proyek  = Pemasukan Proyek - Pengeluaran Proyek
Laba Kotor per CV/PT   = Total Laba Kotor semua proyek CV/PT
Laba Bersih Korporasi  = Total Laba Kotor semua CV/PT - Pengeluaran Korporasi
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
resources/views/livewire/laporan/operasional/rekap-proyek.blade.php
resources/views/livewire/laporan/operasional/rekap-pr.blade.php
resources/views/livewire/laporan/operasional/rekap-petty-cash.blade.php
app/Services/LaporanOperasionalService.php
```

**Permission:** `laporan.rekap-proyek, laporan.rekap-pr, laporan.rekap-petty-cash`

---

# MODUL 8 — NOTIFIKASI

**Tabel:**

```
notifikasis
├── id, korporasi_id, user_id (FK)
├── judul, pesan (text)
├── tipe (pr_submit/pr_approve/pr_reject/topup_request/dll)
├── data (jsonb, nullable)
├── is_read (boolean, default false), read_at (nullable)
├── timestamps

notifikasi_setelans
├── id, korporasi_id, event
├── penerima_roles (jsonb array of role_id)
├── penerima_users (jsonb array of user_id, nullable)
├── is_active (boolean), timestamps
```

**Files:**

```
app/Models/Notifikasi.php
app/Models/NotifikasiSetelan.php
app/Services/NotifikasiService.php
database/seeders/NotifikasiSetelanSeeder.php
resources/views/livewire/notifikasi/bell.blade.php
resources/views/livewire/notifikasi/list.blade.php
resources/views/livewire/settings/notifikasi/index.blade.php
```

**Business Rules:**

- Bell icon + badge counter angka
- User bisa tandai sudah dibaca, tidak bisa hapus
- Fully configurable oleh Super Admin
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
Models      → PascalCase singular   (ProyekTim, RapItem)
Tables      → snake_case plural     (proyek_tims, rap_items)
Livewire    → kebab-case path       (proyek/rap/form.blade.php)
Permissions → kebab-case.aksi       (proyek.view, rap.approve)
Services    → PascalCase + Service  (ProyekService, RapService)
Seeders     → PascalCase + Seeder   (RoleSeeder, KlasifikasiSeeder)
```

## Service Layer

- Business logic → `app/Services/`
- Livewire component → hanya handle UI & memanggil Service
- Tidak pakai Repository Pattern (over-engineering untuk skala ini)

---

_— SISKO-Development-detail.md v1.2 —_
