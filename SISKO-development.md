# SISKO — Dokumen Development

## Panduan Coding & Build Order

_Versi 1.1 — Maret 2026_

---

## 1. Tentang Dokumen Ini

Dokumen ini adalah **panduan coding SISKO** — bukan dokumen bisnis. Isinya adalah peta kerja teknis yang menentukan urutan coding berdasarkan dependency antar modul.

**Prinsip utama:**

- Coding dilakukan dari sudut pandang **Super Admin** (akses penuh)
- Setiap modul di-coding lengkap beserta **permission per role**
- Ketika seluruh modul Super Admin selesai → **semua role otomatis terkover**
- Mind Map per role lain (Dirut, Dir Ops, dll) berfungsi sebagai **checklist testing**, bukan panduan coding

**Stack teknologi:**
| Teknologi | Versi |
|-----------|-------|
| Laravel | 12.x |
| Livewire | 4.x (Single-file components) |
| Flux UI Pro | 2.13.0 |
| Spatie Laravel Permission | latest |
| PostgreSQL | 16.x |
| Vite | 7.x |
| Tailwind CSS | 4.x |
| Pest | latest |

---

## 2. Metode Kerja

Cara kita bekerja bersama saat coding SISKO:

- **Learning by doing** — langsung coding, bertahap sangat kecil per langkah
- **Satu command, jalankan, cek hasilnya, baru lanjut** — tidak loncat-loncat
- **Setiap ada error, selesaikan dulu** sebelum lanjut ke langkah berikutnya
- **Tidak mengulang langkah yang sudah beres**
- **Komentar kode wajib dalam Bahasa Indonesia** yang detail

---

## 2a. Tools & Environment

| Tool           | Keterangan                                                    |
| -------------- | ------------------------------------------------------------- |
| WSL Ubuntu     | Environment utama untuk semua command                         |
| VS Code        | Editor (Windows, dibuka dari WSL: "code .")                   |
| DBeaver        | Database GUI untuk PostgreSQL                                 |
| Claude Code    | AI coding assistant di terminal WSL                           |
| Claude Desktop | AI assistant + MCP untuk sesi chat                            |
| MCP GitHub     | @github/github-mcp-server (PAT di claude_desktop_config.json) |
| GitHub         | Repo: https://github.com/mhdreedho/sisko                      |

---

## 3. Alur Coding per Modul

Setiap modul dikerjakan dengan urutan berikut:

```
1. Buat migration & model
2. Buat Livewire component
3. Buat routes
4. Set permission per role
5. Test per role
6. Commit
```

---

## 4. Mind Map Super Admin — Build Order & Dependency Map

> Urutan ini adalah urutan coding. Modul di atas harus selesai sebelum modul di bawahnya bisa dikerjakan.

```
SISKO
│
├── 0. FONDASI ✅ SELESAI
│   ├── Laravel 12
│   ├── Livewire 4
│   ├── Flux UI Pro 2.13
│   ├── Spatie Permission
│   └── PostgreSQL 16
│
├── 1. AUTH & AKSES
│   ├── Login & Logout ✅ SELESAI
│   ├── Lupa Password (via email)
│   ├── Ganti Password
│   ├── Role Management
│   │   ├── Role Default (6 role, tidak bisa dihapus)
│   │   └── Role Custom (CRUD via UI)
│   ├── Permission Management
│   │   ├── Permission default (tidak bisa dihapus)
│   │   └── Permission extra (bisa ditambah/hapus Super Admin)
│   └── Audit Trail
│
├── 2. MASTER DATA
│   ├── Provinsi & Kota
│   ├── Jenis Kontak
│   ├── Bank
│   ├── Rekening
│   └── Klasifikasi Internal
│       ├── Kategori
│       └── Item
│
├── 3. ENTITAS UTAMA
│   ├── Korporasi (1 data, edit only)
│   ├── Perusahaan CV/PT
│   │   └── Dokumen Perusahaan
│   ├── Pemberi Kerja
│   │   └── Kontak PIC
│   ├── Vendor
│   │   └── Kontak PIC
│   └── Investor
│
├── 4. USER MANAGEMENT
│   ├── Profil User
│   │   ├── Data Pribadi
│   │   ├── Data Pekerjaan
│   │   ├── Data Alamat
│   │   └── Data Darurat
│   └── Akun User
│       ├── Buat Akun
│       ├── Set/Reset Password
│       └── Nonaktifkan Akun
│
├── 5. PROYEK
│   ├── Data Proyek
│   │   ├── Inisiasi
│   │   ├── Pelengkapan
│   │   └── Approve (Draft → Aktif)
│   ├── Assign Tim Proyek
│   ├── RAP
│   │   ├── Hierarki Kategori & Item
│   │   ├── Mapping Klasifikasi Internal
│   │   └── Approve/Reject
│   └── Addendum
│
├── 6. OPERASIONAL
│   ├── Progress Lapangan
│   │   ├── Input per Item RAP
│   │   ├── Foto Dokumentasi
│   │   └── Review
│   ├── Purchase Request (PR)
│   │   ├── Buat PR (Proyek/Korporasi)
│   │   ├── Approval Flow
│   │   ├── Penerimaan Barang
│   │   └── Cancel
│   ├── Petty Cash
│   │   ├── Input Pengeluaran
│   │   ├── Limit per User
│   │   └── Review
│   ├── Top-up Petty Cash
│   │   ├── Request
│   │   └── Approval
│   ├── Buku Kas (read only)
│   └── Pemasukan Proyek
│       ├── 5 Tipe Pemasukan
│       └── Review
│
├── 7. LAPORAN & DASHBOARD
│   ├── Dashboard per Role
│   ├── Laporan Proyek
│   │   ├── Keuangan per Proyek
│   │   ├── Progress per Proyek
│   │   ├── Pengeluaran per Proyek
│   │   └── Pemasukan per Proyek
│   ├── Laporan Keuangan Korporasi
│   │   ├── Rekap Laba/Rugi per CV/PT
│   │   ├── Rekap Laba/Rugi Korporasi
│   │   ├── Rekap Pengeluaran Korporasi
│   │   └── Laporan Arus Kas
│   └── Laporan Operasional
│       ├── Rekap Semua Proyek
│       ├── Rekap PR
│       └── Rekap Petty Cash
│
└── 8. NOTIFIKASI
    ├── Bell Icon + Badge Counter
    ├── Tandai Sudah Dibaca
    └── Setelan Notifikasi (Super Admin)
```

---

## 5. Catatan UI — Navigasi Multi-level

Flux UI Pro sidebar mendukung maksimal **2 level** secara native:

- **Level 1** → `flux:sidebar.group` (dengan icon & heading)
- **Level 2** → `flux:sidebar.item` (menu item)

Untuk konten yang membutuhkan **level 3**, gunakan:

- **Tab** di dalam halaman konten
- **Sub-header** di area konten

---

## 6. Checklist Testing per Role

> Setelah semua modul Super Admin selesai, lakukan testing menggunakan checklist berikut per role.

### 6.1 Direktur Utama

- [ ] Bisa lihat semua proyek
- [ ] Bisa approve proyek (Draft → Aktif)
- [ ] Bisa approve RAP
- [ ] Bisa approve/reject PR
- [ ] Bisa approve top-up petty cash
- [ ] Bisa lihat semua laporan keuangan
- [ ] Bisa assign tim proyek
- [ ] Dashboard menampilkan widget yang sesuai

### 6.2 Direktur Operasional

- [ ] Hanya bisa lihat proyek yang di-assign
- [ ] Bisa approve PR proyek ter-assign
- [ ] Bisa review progress lapangan
- [ ] Bisa review petty cash
- [ ] Tidak bisa lihat laporan keuangan korporasi
- [ ] Dashboard tidak menampilkan nilai keuangan

### 6.3 Sekretaris

- [ ] Bisa lihat semua proyek
- [ ] Bisa input data proyek (inisiasi & pelengkapan)
- [ ] Bisa assign tim proyek
- [ ] Bisa input pemasukan proyek
- [ ] Tidak bisa approve proyek
- [ ] Dashboard sesuai hak akses

### 6.4 Admin

- [ ] Hanya bisa lihat proyek yang di-assign
- [ ] Bisa buat PR
- [ ] Bisa input petty cash
- [ ] Bisa input progress lapangan
- [ ] Tidak bisa lihat nilai keuangan apapun
- [ ] Dashboard tidak menampilkan nilai keuangan

### 6.5 Pengawas Lapangan

- [ ] Hanya bisa lihat proyek yang di-assign
- [ ] Bisa input progress lapangan + foto
- [ ] Bisa buat PR
- [ ] Bisa input petty cash
- [ ] Tidak bisa lihat nilai keuangan apapun
- [ ] Dashboard tidak menampilkan nilai keuangan

---

_— SISKO-development.md v1.1 —_
