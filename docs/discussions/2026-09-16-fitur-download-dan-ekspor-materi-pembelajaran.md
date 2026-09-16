# Diskusi: Desain & Arsitektur Fitur Download / Ekspor Materi Pembelajaran (PDF, Word, Markdown)

* **Tanggal Inisiasi:** 2026-09-16
* **Inisiator:** Antigravity AI (Pair Programming Assistant) & Developer
* **Status:** `ADOPTED` <!-- Pilihan: DRAFT | OPEN | CONSENSUS | ADOPTED | REJECTED -->
* **Terkait Dokumen:**
  - [`docs/spec/09-materials-copilot/`](../spec/09-materials-copilot/) (Spesifikasi Modul Bahan Ajar & Asisten)
  - [`docs/steering/business-rules.md`](../steering/business-rules.md) (Aturan Hak Akses Rombel & Kelas Siswa)
  - [`docs/steering/coding-standards.md`](../steering/coding-standards.md) (Standar Kode & Larastan Level 9)
  - [`docs/spec/17-design-system/`](../spec/17-design-system/) (Design Tokens, ExportMenu & Komponen UI)

---

## 1. Konteks & Masalah

Saat ini, modul Materi Pembelajaran ([`LearningMaterial`](file:///c:/Users/jejak/Documents/www/Bimtek/aksara/app/Models/LearningMaterial.php)) di Aksara telah mendukung:
1. Pembuatan draf materi otomatis melalui AI Co-Pilot dan penyuntingan visual TipTap editor (`Materials/Edit.vue`).
2. Pembacaan materi interaktif di layar bagi siswa terdaftar (`Materials/Show.vue`).

### Kendala Nyata di Lapangan:
1. **Kebutuhan Akses Luring (Offline Learning):**
   - Siswa sering kali memerlukan materi dalam bentuk cetak fisik (printout A4) atau berkas digital (PDF) saat belajar di rumah dengan keterbatasan kuota/internet.
2. **Kebutuhan Arsip & Administrasi Guru:**
   - Guru memerlukan dokumen Microsoft Word (`.docx`) atau cetak PDF ber-kop resmi sekolah sebagai lampiran perangkat ajar fisik, portofolio akreditasi, atau bahan ajar cadangan jika listrik/internet sekolah padam.
3. **Ketiadaan Pilihan Portabilitas Teks Bersih (Markdown):**
   - Belum ada cara cepat untuk menyalin atau mengunduh teks bersih materi untuk dimasukkan ke aplikasi pencatat atau platform lain.

---

## 2. Usulan Solusi: Modul Download Materi Multi-Format

Solusi dirancang dengan mengikuti pola arsitektur ekspor yang sudah teruji di modul RPP (`LearningPlanExportController`):

### A. Tiga Format Dokumen Utama

1. **Format PDF (Cetak Resmi Sekolah):**
   - Menggunakan layout Blade print-ready (`resources/views/exports/material-pdf.blade.php`) dengan CSS `@media print` presisi tinggi.
   - Dilengkapi Kop Surat Sekolah resmi (dari `SettingService`: Logo, Nama Sekolah, NPSN, Alamat, Kontak).
   - Menampilkan judul, metadata (Mapel, Kelas/Fase, Guru Pengampu, Tanggal Terbit), seksi-seksi materi secara berurutan, dan kotak pertanyaan refleksi.
   - Menyertakan tombol cetak interaktif (`exports.partials.print-button`) untuk mencetak langsung atau memilih *Save as PDF*.

2. **Format Microsoft Word (.docx):**
   - Menghasilkan berkas Word resmi menggunakan library `PhpOffice\PhpWord`.
   - Mengonversi seksi materi dan pertanyaan refleksi ke dalam paragraf, bullet list, dan tabel metadata terstruktur dengan warna dan tipografi Aksara.
   - Memiliki footer nomor halaman dan tanggal unduh otomatis.

3. **Format Markdown (.md):**
   - Menghasilkan berkas teks murni `.md` dengan header YAML frontmatter (metadata judul, mapel, kelas, guru), heading `#`/`##`, teks bersih, dan kutipan refleksi `> `.

### B. Aturan Otorisasi & Keamanan Data (RBAC)

- **Siswa (Student):**
  - Hanya dapat mengunduh materi yang **berstatus `published`** dan **terdaftar pada rombel kelasnya** (`belongsToClass($plan->class_id)`).
  - Upaya mengakses materi draf atau materi rombel lain akan diblokir dengan kode HTTP 403 Forbidden.
  - Setiap tindakan unduhan siswa akan mencatat `LearningEvent` berjenis `material_opened` jika belum tercatat.
- **Guru (Teacher):**
  - Dapat mengunduh semua materi yang dimilikinya (`$plan->teacher_id === $user->id`), baik berstatus `draft` maupun `published`.
- **Administrator:**
  - Memiliki hak oversight untuk mengunduh materi dari guru manapun di sekolah.

### C. Integrasi Antarmuka Pengguna (UI)

1. **Halaman Baca Materi ([`Materials/Show.vue`](file:///c:/Users/jejak/Documents/www/Bimtek/aksara/resources/js/Pages/Materials/Show.vue)):**
   - Tambahkan tombol menu dropdown **"Unduh Materi"** di bagian header menggunakan komponen [`ExportMenu.vue`](file:///c:/Users/jejak/Documents/www/Bimtek/aksara/resources/js/Components/ui/ExportMenu.vue) dengan opsi PDF, Word, dan Markdown.
2. **Halaman Daftar Materi ([`Materials/Index.vue`](file:///c:/Users/jejak/Documents/www/Bimtek/aksara/resources/js/Pages/Materials/Index.vue)):**
   - Tambahkan `ExportMenu` pada kolom aksi setiap baris tabel sehingga guru dan siswa dapat mengunduh materi secara instan.

---

## 3. Rencana Komponen & File

| Layer | File / Komponen | Peran |
| :--- | :--- | :--- |
| **Service** | `app/Services/MaterialExportService.php` | Generator berkas Word (`.docx`), Markdown (`.md`), dan data cetak PDF. |
| **Controller** | `app/Http/Controllers/Materials/MaterialExportController.php` | Endpoint pengatur unduhan dan validasi otorisasi multi-role. |
| **View (PDF)** | `resources/views/exports/material-pdf.blade.php` | Halaman cetak PDF ber-kop sekolah standar Aksara. |
| **Routes** | `routes/web.php` | Rute `materials.export.single` (`/{material}/export/{format}`). |
| **Frontend** | `resources/js/Pages/Materials/Show.vue` | Tombol dropdown unduh di header materi. |
| **Frontend** | `resources/js/Pages/Materials/Index.vue` | Tombol menu unduh per baris tabel materi. |
| **Tests** | `tests/Feature/MaterialExportTest.php` | Pengujian fitur otomatis lengkap (hak akses, headers, tipe MIME). |

---

## 4. Konsensus & Langkah Selanjutnya

* **Next Steps:**
  1. [x] Persetujuan pengguna terhadap rencana implementasi.
  2. [x] Pembuatan `MaterialExportService.php` dan `MaterialExportController.php`.
  3. [x] Pembuatan template view cetak PDF `resources/views/exports/material-pdf.blade.php`.
  4. [x] Registrasi rute di `routes/web.php` dan update controller `MaterialController.php`.
  5. [x] Pemasangan `ExportMenu` di `Materials/Show.vue` dan `Materials/Index.vue`.
  6. [x] Pembuatan test suite di `tests/Feature/MaterialExportTest.php`.
  7. [x] Verifikasi PHPStan Level 9 murni (0 error), Pest suite, Vitest, dan Vite build.
