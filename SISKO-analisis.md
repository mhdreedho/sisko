# SISKO — Sistem Informasi Manajemen Kontraktor
## Dokumen Analisis Sistem
*Versi 1.0 — Maret 2026*

---
---

# BAGIAN 1 — LATAR BELAKANG & KONTEKS BISNIS

---

## 1.1 Latar Belakang

Perusahaan ini bergerak di bidang jasa konstruksi dengan struktur korporasi yang menaungi beberapa badan usaha (CV/PT). Mayoritas proyek yang dikerjakan berasal dari pengadaan langsung (Penunjukan Langsung / PL) dari instansi pemerintah Provinsi Sumatera Selatan, dengan nilai proyek berkisar antara Rp 100 juta hingga Rp 400 juta dan durasi pengerjaan rata-rata kurang dari satu bulan.

Alur dokumen pengadaan yang berlaku adalah **SPPBJ → SPK/Kontrak**, tanpa melalui proses tender. Untuk V1, proyek tender tidak termasuk dalam cakupan sistem.

---

## 1.2 Permasalahan yang Ada

Pengelolaan proyek saat ini masih bersifat manual dan tersebar di berbagai media (Excel, WhatsApp, dokumen fisik), sehingga menimbulkan beberapa permasalahan utama:

- **Profitabilitas proyek tidak jelas** — sulit mengetahui apakah suatu proyek menguntungkan atau tidak secara real-time
- **Data tersebar dan tidak terintegrasi** — informasi keuangan, progress lapangan, dan dokumen proyek berada di tempat berbeda
- **Beban input manual** — pencatatan kwitansi, pengeluaran, dan pemasukan dilakukan secara manual dan rawan kesalahan
- **Komunikasi lapangan-kantor tidak terstruktur** — laporan dari lapangan tidak terdokumentasi dengan baik
- **Tidak ada visibilitas lintas proyek** — pimpinan tidak bisa melihat gambaran keseluruhan semua proyek secara bersamaan

---

## 1.3 Visi & Misi

### Visi
Mewujudkan pengelolaan proyek konstruksi yang tertib, transparan, dan terukur melalui sistem informasi yang terintegrasi.

### Misi
- Menyediakan satu platform terpusat untuk seluruh data proyek, keuangan, dan operasional
- Memudahkan pengambilan keputusan pimpinan melalui data yang akurat dan real-time
- Meminimalkan beban administrasi manual di lapangan maupun kantor
- Memberikan visibilitas penuh atas profitabilitas setiap proyek dan korporasi secara keseluruhan

---

## 1.4 Filosofi Produk

> **"Ketenangan bekerja → Ketepatan bekerja"**

Prinsip desain utama:
- **Everything in one glance** — semua informasi penting terlihat tanpa harus buka banyak menu
- **Simplest possible input** — input semudah mungkin untuk hasil yang paling akurat
- **Tidak ada duplikasi data** — satu sumber kebenaran untuk semua informasi

---

## 1.5 Konteks Bisnis

| Aspek | Keterangan |
|-------|-----------|
| Jenis pengadaan | Penunjukan Langsung (PL) dari pemerintah |
| Pemberi kerja | Instansi pemerintah Provinsi Sumatera Selatan |
| Nilai proyek | Rp 100 juta — Rp 400 juta |
| Durasi proyek | Rata-rata kurang dari 1 bulan |
| Alur dokumen | SPPBJ → SPK/Kontrak |
| Struktur usaha | 1 korporasi → beberapa CV/PT → tiap proyek pakai 1 CV/PT |

**Catatan V1:**
- Proyek tender **tidak termasuk** dalam cakupan V1
- Sistem dirancang untuk **multi-tenant** sejak awal (siap untuk SaaS V2)
- Semua tabel utama memiliki kolom `korporasi_id` untuk mendukung arsitektur multi-tenant

---

## 1.6 Struktur Korporasi

Satu korporasi menaungi beberapa badan usaha (CV/PT). Setiap proyek dikerjakan atas nama satu CV/PT.

```
Korporasi
├── CV/PT A
│   ├── Proyek 1
│   ├── Proyek 2
│   └── Proyek N
├── CV/PT B
│   └── Proyek N
└── CV/PT N
```

### Data Korporasi

| Field | Status | Kardinalitas |
|-------|--------|-------------|
| Nama korporasi | Wajib | Single |
| Slogan/motto | Opsional | Single |
| Logo | Opsional | Single |
| Alamat | Wajib | Single |
| Kontak | Opsional | Multiple |
| Rekening | Opsional | Multiple |

**Aturan:**
- Hanya ada **1 korporasi** dalam sistem (tidak bisa ditambah atau dihapus)
- Hanya **Super Admin** yang bisa mengedit data korporasi

---

## 1.7 Struktur Keuangan

```
Laba Kotor per Proyek
= Pemasukan Proyek - Pengeluaran Proyek

Laba Kotor per CV/PT
= Total Laba Kotor semua proyek CV/PT tersebut

Laba Bersih Korporasi
= Total Laba Kotor semua CV/PT - Pengeluaran Korporasi
```

### Kategori Pengeluaran
- **Pengeluaran Proyek** → dibebankan ke proyek tertentu
- **Pengeluaran Korporasi** → semua yang tidak terkait proyek (operasional, gaji tetap, transport prospek, dll)

---
---

# BAGIAN 2 — ROLE & HAK AKSES

---

## 2.1 Role Default

Role default adalah role bawaan sistem yang tidak bisa dihapus.

| # | Role |
|---|------|
| 1 | Super Admin |
| 2 | Direktur Utama |
| 3 | Direktur Operasional |
| 4 | Sekretaris |
| 5 | Admin |
| 6 | Pengawas Lapangan |

---

## 2.2 Aturan Role

- Role default memiliki permission default yang **tidak bisa dihilangkan**
- Super Admin boleh **menambahkan permission extra** ke role default
- Role bisa ditambah & di-custom (role custom) oleh Super Admin melalui UI tanpa coding
- Role custom → permission bebas diatur sepenuhnya oleh Super Admin

---

## 2.3 Role Custom

- Dibuat & dikelola oleh Super Admin melalui UI
- Permission bebas diatur (termasuk akses petty cash on/off)
- Scope akses proyek: semua proyek atau hanya proyek yang di-assign (ditentukan Super Admin)

---

## 2.4 Super Admin

- Akses penuh ke seluruh sistem tanpa pengecualian
- Bisa override semua aturan default
- Satu-satunya role yang bisa mengelola role, permission, notifikasi, dan audit trail

---

## 2.5 Assign Tim Proyek

- Assignment berdasarkan **user (bukan role)**
- Yang bisa assign: **Dirut, Sekretaris & Super Admin**
- Dir Ops: maksimal 1 user per proyek
- Admin & Pengawas: boleh lebih dari 1 user per proyek
- Edit & hapus assign: **Dirut, Sekretaris & Super Admin**
- Notifikasi dikirim ke user yang di-assign saat inisiasi assignment
- Jika user di-unassign dari proyek aktif → akses dicabut langsung, namun semua data yang sudah di-review/approve tetap tersimpan

---

## 2.6 Aturan Akses Proyek

| Role | Akses Proyek |
|------|-------------|
| Super Admin | Semua proyek |
| Direktur Utama | Semua proyek |
| Sekretaris | Semua proyek |
| Direktur Operasional | Hanya proyek yang di-assign |
| Admin | Hanya proyek yang di-assign |
| Pengawas Lapangan | Hanya proyek yang di-assign |
| Role Custom | Tergantung setting Super Admin |

---

## 2.7 Aturan Global Hak Akses

- **Default hak akses** yang sudah disepakati → tidak bisa dihapus/diubah
- Super Admin bisa **menambah** akses extra ke role/user lain
- Yang ditambah Super Admin → bisa dihapus kembali oleh Super Admin
- Berlaku untuk semua modul & fitur di SISKO

---

## 2.8 Aturan Global Keuangan

Karyawan biasa (Admin, Pengawas, role custom tanpa izin khusus) **tidak boleh melihat informasi keuangan** apapun, termasuk:
- Nilai RAP, harga satuan, total anggaran
- Nilai PR & Petty Cash
- Pemasukan proyek
- Laporan keuangan dalam bentuk apapun

Mereka hanya boleh melihat informasi operasional (status proyek, progress fisik, item pekerjaan, bobot pekerjaan).

---

## 2.9 Aturan Global Delete & Edit

- Semua delete → **soft delete** (data tidak benar-benar terhapus)
- Kecuali **draft** → hard delete
- Data yang sudah terkunci (reviewed/approved/closed) → tidak bisa diedit atau dihapus oleh siapapun kecuali ada pengecualian yang disebutkan secara eksplisit
- Master data yang sudah terhubung ke tabel lain → tidak bisa dihapus (terkunci otomatis oleh sistem)

---
---

# BAGIAN 3 — ALUR & STATUS

---

## 3.1 Status Proyek

```
Draft → Aktif → Suspended → Aktif
              → Finish → Under Review ↔ Finish
                       → Closed (FINAL)
```

| Status | Keterangan |
|--------|-----------|
| Draft | Data awal sedang diinput, belum diapprove |
| Aktif | Sudah diapprove Dirut, proyek berjalan |
| Suspended | Proyek dihentikan sementara, semua aktivitas terkunci |
| Finish | Semua pekerjaan selesai, syarat penutupan terpenuhi |
| Under Review | Sedang dalam proses audit |
| Closed | Final, tidak bisa berubah ke status apapun |

**Aturan:**
- Yang bisa ubah status: **Dirut & Super Admin** (default), bisa ditambah role lain oleh Super Admin
- **Super Admin** → bebas ubah ke status apapun termasuk dari Closed
- Proyek **Suspended** → semua aktivitas terkunci sampai kembali Aktif
- Proyek **Finish** → masih bisa input pemasukan, pengeluaran terkunci
- Proyek **Closed** → semua data terkunci total
- Log perubahan status: bisa dilihat oleh **Super Admin, Dirut, Sekretaris**

---

## 3.2 Tahapan Pengerjaan Proyek

```
1. Inisiasi      → input data awal proyek (Dirut atau Sekretaris)
2. Pelengkapan   → input data detail proyek (Dirut atau Sekretaris, boleh beda orang)
3. Approve       → Dirut approve → status Aktif → data proyek terkunci
4. Assign Tim    → Dirut/Sekretaris/Super Admin assign user ke proyek
5. RAP           → input RAP → approve Dirut
6. Operasional   → Progress, PR, Petty Cash, dll (hanya setelah RAP di-approve)
```

Semua fitur yang butuh approval bisa disimpan sebagai **Draft** dulu dan dilanjutkan kapan saja.

---

## 3.3 Status RAP

```
Draft → Pending → Approved (terkunci total)
               → Rejected → Draft (bisa diedit & submit ulang, entry sama)
```

---

## 3.4 Status PR (Purchase Request)

```
Draft → Pending → Approved → Done (terkunci total)
                → Rejected (bisa buat entry baru sebagai revisi)
       Approved → Cancelled (final, tidak bisa direaktivasi)
```

---

## 3.5 Status Petty Cash

```
Draft → Submitted
```
Review = flag/centang "sudah direview" + komentar opsional. Setelah direview → terkunci total.

---

## 3.6 Status Pemasukan Proyek

```
Draft → Submitted
```
Review = flag/centang "sudah direview" + komentar opsional. Setelah direview → terkunci total.

---

## 3.7 Status Progress Lapangan

```
Draft → Submitted
```
Review = flag/centang "sudah direview" + komentar opsional. Setelah direview → terkunci total.

---

## 3.8 Status Top-up Petty Cash

```
Pending → Approved
        → Rejected
```

---

## 3.9 Syarat Proyek Bisa di-Finish

Semua syarat berikut harus terpenuhi:
1. Progress semua item RAP sudah **100%**
2. Tidak ada PR yang masih **pending approval**
3. **BAST** sudah diupload

Jika belum terpenuhi → tombol Finish tidak bisa diklik + notifikasi syarat yang belum terpenuhi ditampilkan.

---
---

# BAGIAN 4 — MODUL & FITUR

---

## 4.1 Data Proyek

### Field Data Proyek

**Identitas:**
| Field | Status | Kardinalitas |
|-------|--------|-------------|
| Nama proyek | Wajib | Single |
| Nomor SPPBJ | Opsional | Single |
| Nomor kontrak/SPK | Opsional | Single |
| Tahun anggaran | Wajib | Single |
| CV/PT yang mengerjakan | Wajib | Single |
| Pemberi Kerja | Wajib | Single |

**Nilai & Waktu:**
| Field | Status | Kardinalitas |
|-------|--------|-------------|
| Nilai kontrak | Wajib | Single (auto-update dari Addendum) |
| Tanggal mulai | Wajib | Single |
| Tanggal selesai kontrak | Wajib | Single |
| Tanggal selesai realisasi | Opsional | Single |

**Lokasi:**
| Field | Status | Kardinalitas |
|-------|--------|-------------|
| Kota & Provinsi | Wajib | Single |
| Alamat/lokasi proyek | Wajib | Single |
| Koordinat GPS | Opsional | Single |

**Lainnya:**
| Field | Status | Kardinalitas |
|-------|--------|-------------|
| Keterangan | Opsional | Single |
| Dokumen | Opsional | Multiple entry, multiple file |
| Tim proyek | — | Relasi ke Profil User |

### Hak Akses Data Proyek

| Aksi | Siapa |
|------|-------|
| Input (Inisiasi & Pelengkapan) | Dirut, Sekretaris |
| Approve (Draft → Aktif) | Dirut |
| Edit (status Draft) | Super Admin, Dirut, Sekretaris |
| Edit (status Aktif ke atas) | Terkunci |
| Soft delete | Super Admin |
| Lihat (semua proyek) | Super Admin, Dirut, Sekretaris |
| Lihat (proyek ter-assign) | Dir Ops, Admin, Pengawas |
| Upload/Edit/Hapus BAST | Super Admin, Dirut, Sekretaris (terkunci jika Closed) |

---

## 4.2 RAB

- Tidak diinput detail ke sistem
- Cukup upload dokumen RAB dari Pemberi Kerja (foto/PDF) sebagai lampiran di data proyek
- Nilai kontrak diinput manual di data proyek (bukan hasil kalkulasi RAB)

---

## 4.3 RAP (Rencana Anggaran Pelaksanaan)

### Struktur RAP

- Hierarki kategori **unlimited level** + item di ujung
- Kategori → hanya untuk organisasi & pengelompokan
- Setiap item: nama, satuan, volume, harga satuan, total (otomatis)
- Total otomatis kalkulasi ke atas sampai total RAP
- Setiap **item** di-mapping ke **Klasifikasi Internal**
- Kategori tidak perlu di-mapping

**Contoh hierarki:**
```
Pekerjaan Sipil (Kategori L1)
└── Struktur (Kategori L2)
    └── Beton Bertulang (Kategori L3)
        └── Beton K-250 (Item) → mapping ke Klasifikasi: Beton
```

### Hak Akses RAP

| Aksi | Siapa |
|------|-------|
| Input & Edit (Draft) | Super Admin, Dirut, Sekretaris |
| Submit untuk approval | Super Admin, Dirut, Sekretaris |
| Approve/Reject | Dirut (default), bisa ditambah Super Admin |
| Lihat penuh (semua field) | Super Admin, Dirut, Sekretaris, Dir Ops ter-assign |
| Lihat terbatas (item & bobot saja) | Admin, Pengawas ter-assign |
| Edit/Hapus (Approved) | Terkunci total, tidak bisa siapapun |

---

## 4.4 Addendum

- Hanya dibuat saat proyek status **Aktif**
- Tidak perlu approval
- Isi perubahan: nilai kontrak, waktu, dan/atau lingkup pekerjaan
- Perubahan RAP: input item yang berubah saja (bukan full RAP ulang)
- Upload dokumen addendum: opsional
- Terkunci jika proyek sudah **Closed**

### Hak Akses Addendum

| Aksi | Siapa |
|------|-------|
| Input | Super Admin, Dirut, Sekretaris |
| Edit & Hapus | Super Admin, Dirut |
| Lihat histori | Super Admin, Dirut, user ter-assign proyek terkait |

---

## 4.5 Progress Lapangan

- Diinput **per item RAP**
- Foto dokumentasi: **wajib minimal 1 foto** per item yang diinput
- Ada catatan/keterangan dari inputer
- Progress fisik (%) → input manual per item RAP
- Budget terpakai (%) → otomatis dari realisasi pengeluaran vs RAP
- Indikator on-track / tertinggal → perbandingan keduanya

### Hak Akses Progress Lapangan

| Aksi | Siapa |
|------|-------|
| Input | Semua role default, ikut aturan assign proyek |
| Edit & Hapus (belum direview) | Super Admin |
| Review (flag/centang) | Super Admin, Dirut, Dir Ops ter-assign |
| Setelah direview | Terkunci total |

---

## 4.6 Purchase Request (PR)

### Aturan PR

- Multi item dalam 1 PR
- 1 PR = 1 proyek atau korporasi
- Dokumentasi penerimaan barang: upload foto/dokumen, multiple file, opsional
- PR yang ditolak → revisi dibuat sebagai **entry baru** (entry lama tetap tersimpan sebagai record)
- PR Cancelled → final, tidak bisa direaktivasi

### Approval PR

- Pengeluaran Proyek → notif & approve oleh Dirut & Dir Ops proyek terkait (siapa duluan)
- Pengeluaran Korporasi → hanya Dirut
- Bisa ditambah role lain oleh Super Admin

### Penerimaan Barang

- Yang bisa input: semua role default, ikut aturan assign proyek
- Review penerimaan: Super Admin, Dirut, Dir Ops ter-assign
- Setelah direview → terkunci total

### Hak Akses PR

| Aksi | Siapa |
|------|-------|
| Buat PR | Semua role default, ikut aturan assign proyek |
| Approve/Reject | Dirut, Dir Ops ter-assign (proyek); Dirut (korporasi) |
| Cancel (status Approved) | Super Admin, Dirut |
| Edit & Hapus | Super Admin (hanya sebelum Done) |
| Lihat (semua proyek) | Super Admin, Dirut, Sekretaris |
| Lihat (proyek ter-assign) | Dir Ops, Admin, Pengawas |
| Status Done | Terkunci total |

---

## 4.7 Petty Cash

### Aturan Petty Cash

- Tidak perlu approval, langsung catat
- Upload bukti pengeluaran: multiple file, opsional
- Ada limit per user: max saldo & max minus (0 = unlimited)
- Jika role custom tidak diaktifkan petty cash → menu tidak tampil sama sekali
- 1 Petty Cash = 1 proyek (ter-assign) atau korporasi

### Notifikasi Petty Cash

- Pengeluaran proyek → notif ke Dirut & Dir Ops proyek terkait
- Pengeluaran korporasi → notif ke Dirut & Sekretaris

### Hak Akses Petty Cash

| Aksi | Siapa |
|------|-------|
| Input | Semua role default; role custom (jika diaktifkan) |
| Lihat draft milik sendiri | Pemilik, Super Admin |
| Edit & Hapus (belum direview) | Super Admin |
| Review (flag/centang) | Super Admin, Dirut, Dir Ops ter-assign |
| Set limit per user | Super Admin, Dirut |
| Setelah direview | Terkunci total |

---

## 4.8 Top-up Petty Cash

### Aturan Top-up

- Request: semua role yang punya fitur petty cash
- Approval: Dirut (default, tidak bisa dihapus), bisa ditambah Super Admin
- Siapa duluan approve, selesai
- Saldo bertambah setelah di-approve (bukan saat request)
- Request pending → bisa dibatalkan oleh requester & Super Admin
- Request approved → terkunci, tidak bisa diedit/dihapus

### Hak Akses Histori Top-up

| Role | Akses |
|------|-------|
| Super Admin | Semua |
| Dirut | Semua |
| Dir Ops | Hanya user di proyek ter-assign |
| Pemilik | Milik sendiri |

---

## 4.9 Buku Kas Petty Cash

Catatan mutasi saldo petty cash per user. Terbentuk otomatis dari transaksi — tidak bisa diedit atau dihapus (read only).

```
Saldo Awal
+ Top-up (setiap top-up yang di-approve)
- Pengeluaran (setiap transaksi petty cash)
= Saldo Akhir (realtime)
```

Tiap baris mutasi: tanggal, jenis (Top-up/Pengeluaran), keterangan, nominal, saldo berjalan.

**Saldo bisa minus** — ada batas max minus per user.

### Hak Akses Buku Kas

| Role | Akses |
|------|-------|
| Super Admin | Semua user |
| Dirut | Semua user |
| Dir Ops | Hanya user di proyek ter-assign |
| Pemilik | Milik sendiri |

---

## 4.10 Pemasukan Proyek

### 5 Tipe Pemasukan

| # | Tipe | Keterangan |
|---|------|-----------|
| 1 | Uang Muka | — |
| 2 | Termin | — |
| 3 | Pembayaran Akhir | — |
| 4 | Uang Retensi | — |
| 5 | Pembayaran Penuh | Khusus pengadaan barang/jasa tanpa retensi |

- Semua opsional, tidak harus semua ada di setiap proyek
- Nilai dicatat sebagai nominal, sistem otomatis hitung sisa dari nilai kontrak
- Upload bukti: multiple file, opsional

### Hak Akses Pemasukan Proyek

| Aksi | Siapa |
|------|-------|
| Input | Super Admin, Dirut, Sekretaris, Dir Ops ter-assign |
| Edit (belum direview) | Super Admin, Dirut |
| Hapus (belum direview) | User yang menginput |
| Review (flag/centang) | Super Admin, Dirut, Dir Ops ter-assign |
| Lihat | Super Admin, Dirut, Sekretaris, Dir Ops ter-assign |
| Setelah direview | Terkunci total |

---
---

# BAGIAN 5 — MASTER DATA

---

## 5.1 Aturan Umum Master Data

- Input, Edit & Hapus master data: **Super Admin, Dirut, Sekretaris**
- Master data yang sudah terhubung ke tabel lain → **tidak bisa dihapus** (terkunci otomatis)
- Semua delete → soft delete
- Format file yang diizinkan untuk upload: **PDF, JPG, PNG**
- Semua dokumen bisa di-download sebagai **bundle PDF** (pilih semua atau sebagian, urutan bisa diatur)

---

## 5.2 Perusahaan (CV/PT)

### Data Perusahaan

| Field | Status | Kardinalitas |
|-------|--------|-------------|
| Nama perusahaan | Wajib | Single |
| Jenis (CV/PT) | Wajib | Single |
| Kontak | Opsional | Multiple |
| Rekening | Opsional | Multiple |
| Dokumen | Opsional | Multiple entry, multiple file |

### Jenis Dokumen Perusahaan (Default)

| # | Nama Jenis | Singkatan |
|---|-----------|-----------|
| 1 | Akta Notaris | AKTA |
| 2 | Surat Keterangan Pendaftaran Perubahan | SKPP |
| 3 | BPJS Ketenagakerjaan | BPJSTK |
| 4 | NPWP Perusahaan | NPWP |
| 5 | SPT Tahunan | SPT |
| 6 | Konfirmasi Status Wajib Pajak | KSWP |
| 7 | KTP Direktur | KTPDIR |
| 8 | NPWP Direktur | NPWPDIR |
| 9 | Surat Keterangan Domisili Usaha | SKDU |
| 10 | Kartu Tanda Anggota Gapensi | KTA |
| 11 | Sertifikat Badan Usaha | SBU |
| 12 | OSS Risk Based Approach | OSS RBA |
| 13 | Sertifikat Kompetensi Kerja | SKK |
| 14 | KTP Pemegang SKK | KTPSKK |
| 15 | NPWP Pemegang SKK | NPWPSKK |
| 16 | Dokumen Pendukung Lainnya | *(custom)* |

Jenis dokumen di atas adalah **default** — bisa ditambah jenis lain sesuai kebutuhan.

---

## 5.3 Pemberi Kerja

### Data Pemberi Kerja

| Field | Status | Kardinalitas |
|-------|--------|-------------|
| Nama instansi/perusahaan | Wajib | Single |
| Jenis (Pemerintah/Swasta) | Wajib | Single |
| Kota & Provinsi | Wajib | Single |
| Alamat | Opsional | Single |
| No. telepon/email | Opsional | Single |
| Kontak PIC | Opsional | Multiple |
| Rekening | Opsional | Multiple |
| Dokumen | Opsional | Multiple entry, multiple file |

### Data Kontak PIC

| Field | Kardinalitas |
|-------|-------------|
| Nama lengkap | Single |
| Jabatan | Single |
| Kontak | Multiple (pakai Master Jenis Kontak) |

Tidak ada default jenis dokumen — semua custom, boleh kosong.

---

## 5.4 Vendor

### Data Vendor

| Field | Status | Kardinalitas |
|-------|--------|-------------|
| Nama | Wajib | Single |
| Jenis (Supplier Material/Jasa/Subkontraktor) | Wajib | Multiple |
| Kota & Provinsi | Wajib | Single |
| Alamat | Opsional | Single |
| No. telepon/email | Opsional | Single |
| NPWP | Opsional | Single |
| Kontak PIC | Opsional | Multiple |
| Rekening | Opsional | Multiple |
| Dokumen | Opsional | Multiple entry, multiple file |

### Data Kontak PIC

| Field | Kardinalitas |
|-------|-------------|
| Nama lengkap | Single |
| Jabatan | Single |
| Kontak | Multiple (pakai Master Jenis Kontak) |

---

## 5.5 Investor

### Data Investor

| Field | Status | Kardinalitas |
|-------|--------|-------------|
| Nama | Wajib | Single |
| Jenis (Perorangan/Perusahaan) | Wajib | Single |
| Kota & Provinsi | Opsional | Single |
| Alamat | Opsional | Single |
| Referral karyawan | Opsional | Single (relasi ke Profil User) |
| Kontak | Opsional | Multiple |
| Rekening | Opsional | Multiple |
| Dokumen | Opsional | Multiple entry, multiple file |

---

## 5.6 Profil User & Akun User

### Data Profil User

**Data Pribadi:**
| Field | Status | Kardinalitas |
|-------|--------|-------------|
| Nama lengkap | Wajib | Single |
| Nama panggilan | Opsional | Single |
| Jenis kelamin | Wajib | Single |
| Tempat lahir | Opsional | Single |
| Tanggal lahir | Opsional | Single |
| Agama | Opsional | Single |
| Status pernikahan | Opsional | Single |
| Kewarganegaraan (WNI/WNA) | Opsional | Single |
| NIK | Opsional | Single |
| NPWP pribadi | Opsional | Single |
| Foto profil | Opsional | Single |
| Status profil (Aktif/Nonaktif) | Wajib | Single |

**Data Pekerjaan:**
| Field | Status | Kardinalitas |
|-------|--------|-------------|
| Jabatan | Wajib | Single |
| Departemen | Opsional | Single |
| Status karyawan (Tetap/Kontrak/Freelance/Lainnya) | Wajib | Single |
| Tanggal bergabung | Opsional | Single |
| Tanggal berakhir kontrak | Opsional | Single |

**Data Alamat:**
| Field | Status | Kardinalitas |
|-------|--------|-------------|
| Alamat KTP | Opsional | Single |
| Alamat domisili | Opsional | Single |
| Kota & Provinsi | Opsional | Single |

**Data Darurat:**
| Field | Status | Kardinalitas |
|-------|--------|-------------|
| Nama kontak darurat | Opsional | Single |
| Hubungan | Opsional | Single |
| No. HP kontak darurat | Opsional | Single |

**Relasi:**
| Field | Kardinalitas |
|-------|-------------|
| Kontak | Multiple |
| Rekening | Multiple, opsional |
| Dokumen | Multiple entry, multiple file |

### Data Akun User

| Field | Status | Keterangan |
|-------|--------|-----------|
| Email | Wajib | Untuk login |
| Password | Wajib | Bisa auto-generate, opsional kirim via email |
| Role | Wajib | Pilih dari master role |
| Status akun (Aktif/Nonaktif) | Wajib | Nonaktif profil → otomatis nonaktifkan akun |

**Catatan:**
- 1 Profil User → maksimal 1 Akun User
- Akun bisa dinonaktifkan tanpa hapus profil
- User bisa ganti password sendiri
- Ada fitur lupa password via email
- Super Admin bisa force reset password tanpa perlu tahu password lama

### Hak Akses Profil & Akun User

| Aksi | Siapa |
|------|-------|
| Lihat daftar profil (semua) | Super Admin, Dirut |
| Lihat daftar profil (proyek ter-assign) | Semua role lainnya |
| Buat profil | Super Admin |
| Edit profil | Super Admin, pemilik profil |
| Nonaktifkan profil | Super Admin |
| Kelola akun (buat/nonaktifkan/reset password) | Super Admin |
| Lihat daftar akun | Super Admin |
| Ganti password sendiri | Pemilik akun |

---

## 5.7 Master Jenis Kontak

Dipakai oleh semua entitas yang memiliki data kontak.

| Field | Keterangan |
|-------|-----------|
| Nama jenis | Wajib |
| Icon | Opsional |

**Default jenis kontak:**

| # | Nama Jenis |
|---|-----------|
| 1 | No. Telepon |
| 2 | No. HP / WhatsApp |
| 3 | Email |
| 4 | Alamat |
| 5 | Koordinat GPS |
| 6 | Website |
| 7 | Facebook |
| 8 | Instagram |

Bisa ditambah jenis lain sesuai kebutuhan. Terkunci jika sudah dipakai.

---

## 5.8 Master Bank

| Field | Keterangan |
|-------|-----------|
| Nama bank | Wajib |
| Singkatan | Wajib |
| Kode bank | Wajib |

Dipakai oleh Master Rekening. Terkunci jika sudah dipakai.

---

## 5.9 Master Rekening

Dipakai oleh: Korporasi, Perusahaan, Pemberi Kerja, Vendor, Investor, Profil User.

| Field | Status |
|-------|--------|
| Nama bank | Wajib (relasi ke Master Bank) |
| No. rekening | Wajib |
| Atas nama | Wajib |
| Cabang | Opsional |
| Jenis rekening (Giro/Tabungan) | Wajib |

---

## 5.10 Master Provinsi & Kota

Hierarkis: Provinsi → Kota/Kabupaten. Terkunci jika sudah dipakai di entitas manapun.

---

## 5.11 Klasifikasi Internal

Dipakai untuk mapping item RAP lintas proyek — memungkinkan analisa pengeluaran per kategori di semua proyek.

### Struktur

```
Kategori
└── Item (yang di-mapping dari item RAP)
```

### Default Klasifikasi Internal

**Pekerjaan Sipil**
- Pekerjaan Persiapan, Pekerjaan Tanah, Pekerjaan Pondasi, Pekerjaan Beton, Pekerjaan Besi & Baja, Pekerjaan Pasangan & Plesteran, Pekerjaan Atap, Pekerjaan Lantai, Pekerjaan Dinding & Partisi, Pekerjaan Pintu & Jendela, Pekerjaan Finishing

**Pekerjaan Mekanikal & Elektrikal**
- Pekerjaan Listrik, Pekerjaan Plumbing, Pekerjaan AC & Ventilasi, Pekerjaan Sanitasi

**Pekerjaan Luar**
- Pekerjaan Jalan & Parkir, Pekerjaan Drainase, Pekerjaan Taman & Lansekap, Pekerjaan Pagar & Gerbang

**Pekerjaan Preventif & K3**
- Keselamatan Kerja (K3), Proteksi & Perlindungan, Pengujian & Quality Control

**Tenaga Kerja**
- Mandor, Tukang, Pekerja Harian

**Pengadaan Material**
- Material Bangunan, Material Mekanikal, Material Elektrikal

**Subkontraktor**
- Subkon Sipil, Subkon Mekanikal, Subkon Elektrikal

**Biaya Proyek Lainnya**
- Perizinan & Administrasi, Transportasi & Mobilisasi, Asuransi & Jaminan, Biaya Tak Terduga

### Hak Akses Klasifikasi Internal

| Aksi | Siapa |
|------|-------|
| Input, Edit, Hapus | Super Admin, Dirut, Sekretaris (default), bisa ditambah Super Admin |
| Item yang sudah dipakai di RAP | Terkunci, tidak bisa dihapus |

---
---

# BAGIAN 6 — MANAJEMEN USER

---

## 6.1 Alur Pembuatan User

```
Buat Profil User → Buatkan Akun User (opsional)
```

Semua orang yang ada di sistem (karyawan, investor, dll) masuk sebagai Profil User terlebih dahulu. Akun User hanya dibuat jika orang tersebut butuh akses ke sistem.

---

## 6.2 Fitur Password

- Super Admin bisa set password manual atau auto-generate (random)
- Checkbox: kirim password via email atau tidak
- User bisa ganti password sendiri setelah login
- Ada fitur lupa password via email
- Super Admin bisa force reset password tanpa perlu tahu password lama

---

## 6.3 Audit Trail

Mencatat semua aktivitas di sistem:
- Siapa (user_id)
- Aksi (create/update/delete/view)
- Modul & record yang diakses
- Waktu (timestamp)
- IP address
- Perubahan: field apa yang berubah, nilai sebelum & sesudah (khusus update)

**Hak akses:** Super Admin saja.

---
---

# BAGIAN 7 — LAPORAN & DASHBOARD

---

## 7.1 Laporan Proyek

| # | Laporan | Akses |
|---|---------|-------|
| 1 | Keuangan per proyek | Super Admin, Dirut, Sekretaris, Dir Ops ter-assign |
| 2 | Progress per proyek | Super Admin, Dirut, Sekretaris, Dir Ops, Admin, Pengawas ter-assign |
| 3 | Pengeluaran per proyek (PR & Petty Cash) | Super Admin, Dirut, Sekretaris, Dir Ops ter-assign |
| 4 | Pemasukan per proyek | Super Admin, Dirut, Sekretaris, Dir Ops ter-assign |

**Filter default:** periode, CV/PT, status proyek, Pemberi Kerja

---

## 7.2 Laporan Keuangan Korporasi

| # | Laporan | Akses |
|---|---------|-------|
| 5 | Rekap laba/rugi per CV/PT | Super Admin, Dirut, Sekretaris |
| 6 | Rekap laba/rugi korporasi | Super Admin, Dirut |
| 7 | Rekap pengeluaran korporasi | Super Admin, Dirut |
| 8 | Laporan Arus Kas | Super Admin, Dirut |

**Filter default:** periode, CV/PT

**Laporan Arus Kas** — filter default:
- Periode (tanggal dari - sampai)
- Jenis transaksi (Semua/Pemasukan/Pengeluaran)
- Sumber (Semua/per Proyek/Korporasi)
- CV/PT

---

## 7.3 Laporan Operasional

| # | Laporan | Akses |
|---|---------|-------|
| 9 | Rekap semua proyek (status, progress, timeline) | Super Admin, Dirut, Sekretaris, Dir Ops, Admin, Pengawas ter-assign |
| 10 | Rekap PR | Super Admin, Dirut, Sekretaris, Dir Ops ter-assign |
| 11 | Rekap Petty Cash | Super Admin, Dirut, Sekretaris, Dir Ops ter-assign |

**Filter default:** periode, status proyek, CV/PT

**Rekap Petty Cash** — bisa filter by user (pencarian asynchronous), tampilkan rekap transaksi & saldo per user.

**Export:** semua laporan bisa export ke PDF (V1). Export Excel menyusul.

---

## 7.4 Dashboard per Role

Prinsip: **everything in one glance** — semua info penting langsung terlihat.
Dashboard bisa filter per **periode**. Tidak ada kustomisasi widget (V1).

### Super Admin
- Info sistem (jumlah user aktif, dll)
- Semua widget Dirut

### Direktur Utama
- Ringkasan keuangan korporasi (pemasukan, pengeluaran, laba bersih)
- Daftar proyek aktif + status + progress
- PR pending approval
- Top-up petty cash pending approval
- Notifikasi penting

### Direktur Operasional
- Daftar proyek ter-assign + progress
- PR pending approval (proyek ter-assign)
- Progress lapangan yang belum direview

### Sekretaris
- Daftar proyek aktif + status
- RAP yang belum di-approve
- PR & Petty Cash yang baru diinput

### Admin
- Proyek ter-assign + status
- PR & Petty Cash ter-assign

### Pengawas Lapangan
- Proyek ter-assign
- Progress input terakhir
- PR & Petty Cash

**Catatan:** Dashboard Admin & Pengawas **tidak menampilkan nilai keuangan** apapun.

---

## 7.5 Notifikasi

- Notifikasi ditampilkan di dalam sistem (bell icon) dengan badge counter angka
- User bisa tandai sudah dibaca, tidak bisa hapus notifikasi
- V1: dalam sistem saja. Next: kirim via WhatsApp

### Setelan Notifikasi

- Fully configurable oleh **Super Admin**
- Ada setelan default saat instalasi (bisa diubah atau dihapus semua)
- Tidak mempengaruhi alur sistem apapun

### Default Notifikasi (saat instalasi)

| Event | Penerima Default |
|-------|-----------------|
| PR disubmit | Dirut, Dir Ops proyek terkait |
| PR di-approve | Pembuat PR |
| PR di-reject | Pembuat PR |
| PR di-cancel | Pembuat PR |
| Petty Cash disubmit (proyek) | Dirut, Dir Ops proyek terkait |
| Petty Cash disubmit (korporasi) | Dirut, Sekretaris |
| Request top-up | Dirut |
| Top-up di-approve | Requester |
| Top-up di-reject | Requester |
| Progress diinput | Dir Ops, Dirut proyek terkait |
| RAP disubmit | Dirut |
| RAP di-approve | Pembuat RAP |
| RAP di-reject | Pembuat RAP |
| Status proyek berubah | Tim proyek terkait |
| Assign tim proyek | User yang di-assign |

---
---

*— Akhir Dokumen SISKO-analisis.md v1.0 —*
