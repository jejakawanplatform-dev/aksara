# Changelog — Aksara

Semua perubahan penting pada proyek **Aksara** akan didokumentasikan di berkas ini.

Format berkas ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.1.0/) dan mematuhi prinsip [Semantic Versioning](https://semver.org/lang/id/).

---

## [Unreleased]

### Planned
- **Concurrency & Stress Testing:** Pengujian beban serentak pengerjaan kuis siswa (30+ submission bersamaan).

---

## [0.20.0] - 2026-09-15

### Added
- **Frontend Unit Testing via Vitest:**
  - Konfigurasi `vitest.config.js` dengan path alias `@` ke `./resources/js`.
  - Skrip `test:unit` dan `test:unit:watch` pada `package.json`.
  - Berkas pengujian `tests/Unit/js/useBulkSelect.test.js` (5 skenario: inisialisasi, toggle select, select all, indeterminate, matching across pages).
  - Berkas pengujian `tests/Unit/js/authValidation.test.js` (5 skenario: isFilled, format email, password length, password confirmation, server vs local error resolution).
  - 10/10 pengujian unit JavaScript lolos 100%.
- **Browser E2E Smoke Testing via Playwright (`@playwright/test`):**
  - Konfigurasi `playwright.config.js` dengan Chromium headless engine dan base URL `http://127.0.0.1:8000`.
  - Skrip `test:e2e` pada `package.json`.
  - `tests/e2e/auth-smoke.spec.js`: Uji login otomatis dan verifikasi antarmuka dashboard untuk 3 peran (Admin, Guru, Siswa).
  - `tests/e2e/tiptap-editor.spec.js`: Uji render TipTap editor materi, keberadaan toolbar, interaksi input teks, dan pembukaan dialog MediaPicker modal.
  - `tests/e2e/bulk-actions.spec.js`: Uji interaksi checkbox seleksi tabel pengguna, kemunculan `BulkToolbar`, pembukaan modal konfirmasi dengan *double confirmation guard* (wajib ketik "HAPUS"), dan penutupan aman.
  - 6/6 skenario peramban Playwright lolos 100%.
- **Pembaruan Dokumen Kemudi:**
  - Menambahkan `npm run test:unit` dan `npm run test:e2e` ke QA Gate di `docs/steering/coding-standards.md` dan `docs/steering/testing-strategy.md`.

---

## [0.19.0] - 2026-09-15

### Added
- **Critical Journey Smoke Test (`tests/Feature/Smoke/CriticalJourneySmokeTest.php`):** Pengujian integrasi cepat lintas 5 role sekolah dalam satu skenario terpadu:
  - Admin: Mengakses dashboard, daftar pengguna, setelan AI, dan referensi kurikulum.
  - Guru: Mengakses dashboard guru, daftar RPP, dan membuka TipTap editor materi.
  - Siswa: Membaca materi, mencatat learning event, serta mengerjakan dan submit kuis.
  - Guru (Lanjutan): Menyimpan absensi kehadiran siswa dan formulir evaluasi refleksi pembelajaran.
  - Wali Kelas: Mengakses dashboard dan rekapitulasi absensi kelas.
  - Wali Murid: Mengakses dashboard dan memantau rekaman nilai kuis serta presensi anak.
- **Dokumentasi Spek Tahap 19 (`docs/spec/19-smoke-testing-qa/`):** Berkas spesifikasi teknis lengkap (`plan.md`, `tasks.md`, `implementation.md`, `verification.md`).
- **Protokol QA Wajib Agen AI:** Penambahan Bagian 7 & 8 pada `docs/steering/coding-standards.md` yang mewajibkan 5 QA Gates (Pest 100%, Smoke Test 5-role, PHPStan Level 9 = 0 error, build frontend hijau, dan Pint) sebelum serah-terima tugas.

### Changed
- **PHPStan / Larastan Naik ke Level 9:** Level analisis statis dinaikkan ke level maksimal 9 pada `phpstan.neon` dengan hasil bersih `[OK] No errors`.
- **Baseline Framework Typing (`phpstan-baseline.neon`):** Mengisolasi utang generik framework Eloquent Laravel untuk menjaga kebersihan analisis kode aplikasi.
- Status thread diskusi `docs/discussions/2026-09-15-strategi-smoke-test-dan-gap-test-suite.md` resmi diubah menjadi `ADOPTED`.

---

## [0.18.0] - 2026-09-15

### Added
- **Bulk Actions & Selection Toolkit (Tahap 18):**
  - Composable reaktif `resources/js/Composables/useBulkSelect.js` dengan dukungan seleksi *page-scoped* dan *cross-page matching*.
  - Komponen UI `resources/js/Components/ui/BulkToolbar.vue` yang secara mulus menggantikan baris pencarian/filter saat data terpilih.
  - Komponen UI `resources/js/Components/ui/BulkConfirmModal.vue` dengan pengaman ganda (*double-confirmation guard* mengetik kata `"HAPUS"`).
- **Pilot Module: Manajemen Pengguna (`resources/js/Pages/Users/Index.vue`):**
  - Checkbox terpadu pada header tabel dan setiap baris data.
  - Endpoint backend `POST /users/bulk-destroy` (`UserController@bulkDestroy`) dengan proteksi diri akun yang sedang login dan pencegahan penghapusan guru/wali kelas dengan relasi aktif.
- **Automated Feature Tests (`tests/Feature/UserBulkActionTest.php`):** 6 pengujian komprehensif mencakup skenario sukses, otorisasi 403, proteksi diri, dan proteksi integritas relasional.
- **Dokumentasi Spek Tahap 18 (`docs/spec/18-bulk-actions/`):** Berkas dokumentasi spek teknis lengkap.

---

## [0.17.0] - 2026-08-11

### Added
- **Design System Vue Source of Truth (Tahap 17):**
  - Arsitektur visual tema Coastal Light Enterprise permanen (ADR-012).
  - Standarisasi pustaka komponen `resources/js/Components/ui/`: `Btn`, `Card`, `Field`, `Flash`, `StatusBadge`, `PageHeader`, `Alert`, `EmptyState`, `Table`, `Loading`, `Icon`, `Modal`, `Pagination`, `IconButton`, `ExportMenu`, `PasswordInput`.
- **UI Coastal Rollout:** Pemolesan konsisten pada seluruh layar (Plans, Materials, Users, References, Settings, Attendance, Quiz, Evaluation, Reports, Dashboard).

---

## [0.16.0] - 2026-08-11

### Added
- **Context-Scoped Media Management (Tahap 16):**
  - Penyimpanan aset gambar kontekstual berbasis direktori isolasi `storage/app/public/materials/{id}/` (ADR-008).
  - Dialog `MediaPicker.vue` pada TipTap untuk daftar, unggah, pratinjau, dan hapus gambar materi secara aman.

---

## [0.15.0] - 2026-08-10

### Added
- **TipTap Rich Text Editor Global (Tahap 15):**
  - Integrasi `@tiptap/vue-3` pada `Components/tiptap/TipTapEditor.vue` dan `TipTapToolbar.vue`.
  - Dukungan penulisan rumus matematika LaTeX melalui KaTeX rendering lazy-loaded (`withMath`).
  - Ekstensi tabel, format typography rich-text, dan resizing gambar.

---

## [0.14.0] - 2026-08-10

### Added
- **Ekspor Dokumen Pembelajaran (Tahap 14):**
  - Ekspor RPP / Modul Ajar dan Laporan ke format PDF berbasis Blade layout bersama (`resources/views/exports/*`) dengan kop surat sekolah standar.
  - Ekspor data referensi kurikulum dan rekap kehadiran ke format Excel.

---

## [0.13.0] - 2026-08-10

### Added
- **Dashboard Multi-Role & Monitoring (Tahap 13):**
  - Dashboard Admin (`Dashboard/Admin.vue`) dengan ringkasan pengguna, AI token usage, dan status sistem.
  - Dashboard Guru (`Dashboard/Guru.vue`) dengan daftar rencana aktif dan progres pembelajaran.
  - Dashboard Siswa (`Dashboard/Siswa.vue`) dengan ringkasan materi dan riwayat kuis.
  - Dashboard Wali Kelas (`Dashboard/WaliKelas.vue`) dengan pantauan absensi dan performa rombel.
  - Dashboard Wali Murid (`Dashboard/WaliMurid.vue`) untuk memantau kehadiran dan skor kuis anak.
  - Laporan Guru (`Reports/TeacherReportController.php`).

---

## [0.12.0] - 2026-08-10

### Added
- **Evaluasi & Refleksi Pembelajaran (Tahap 12):**
  - Formulir evaluasi guru pasca-pertemuan (`Evaluation/Form.vue`).
  - Model `TeacherEvaluation` mencakup catatan ketercapaian, kendala lapangan, dan rencana tindak lanjut.
  - Halaman pemantauan keterlaksanaan pembelajaran (`Evaluation/Monitoring.vue`).

---

## [0.11.0] - 2026-08-10

### Added
- **Presensi & Rekap Kehadiran (Tahap 11):**
  - Pencatatan kehadiran siswa per sesi rencana pembelajaran (`Attendance/Form.vue`).
  - Enum `AttendanceStatus` (Hadir, Sakit, Izin, Alpa).
  - Rekapitulasi absensi kelas untuk wali kelas (`Attendance/Summary.vue`).

---

## [0.10.0] - 2026-08-10

### Added
- **Kuis Interaktif Pembelajaran (Tahap 10):**
  - Pembuatan kuis dari rencana pembelajaran oleh guru (`Quiz/Form.vue`).
  - Antarmuka pengerjaan kuis interaktif bagi siswa (`Quiz/Attempt.vue`).
  - Kalkulasi skor otomatis dan pembatasan satu kali percobaan (`quiz_attempts`).

---

## [0.9.0] - 2026-08-09

### Added
- **Materi Pembelajaran & AI Co-Pilot (Tahap 09):**
  - Editor penyusunan materi pembelajaran guru (`Materials/Edit.vue`).
  - Halaman baca materi siswa (`Materials/Show.vue`) dengan pencatatan otomatis `learning_events`.
  - Asisten AI Co-Pilot tunggal untuk membantu guru merevisi, memperluas, dan memformat materi pembelajaran (ADR-009).
  - Sanitasi konten HTML materi (`MaterialContentHtml`) demi mencegah serangan XSS.

---

## [0.8.0] - 2026-08-09

### Added
- **Penyusunan Rencana Pembelajaran (Tahap 08):**
  - Alur pembuatan RPP / Modul Ajar terstruktur (`Plans/Index.vue`, `Plans/Create.vue`).
  - Integrasi pembuatan draf pembelajaran otomatis via AI (`ai_generations`).
  - Alur review wajib oleh guru sebelum materi dipublikasikan (ADR-001).

---

## [0.7.0] - 2026-08-09

### Added
- **Pengaturan Sistem & Provider AI (Tahap 07):**
  - Manajemen katalog provider AI (`ai_providers`) dengan prioritas failover (`priority_order`).
  - Pengaturan model AI pilihan per kategori tugas via `system_settings`.
  - Dukungan mode mock deterministik (`AI_MOCK_MODE=true`) untuk kelancaran bimtek offline tanpa API key eksternal (ADR-002).

---

## [0.6.0] - 2026-08-09

### Added
- **Referensi Kurikulum & Data Sekolah (Tahap 06):**
  - CRUD referensi Elemen CP, Tujuan Pembelajaran (TP), dan Alur Tujuan Pembelajaran (ATP).
  - Manajemen data induk sekolah: rombel/kelas, mata pelajaran, tahun ajaran, dan semester.
  - Fitur ekspor dan impor berkas referensi.

---

## [0.5.0] - 2026-08-09

### Added
- **Manajemen Pengguna (Tahap 05):**
  - CRUD pengguna sekolah dengan pemfilteran berbasis role (`Users/Index.vue`, `Users/Form.vue`).
  - Seeder akun percontohan workshop (`DemoDataSeeder`) untuk 5 role sekolah.

---

## [0.4.0] - 2026-08-09

### Added
- **Role-Based Access Control (Tahap 04):**
  - Implementasi RBAC via Spatie Permission yang dipadukan dengan kolom `users.role` (ADR-003, ADR-007).
  - Matriks perizinan dinamis pada antarmuka `/access` (`Access/Index.vue`).
  - Middleware otorisasi backend ketat untuk setiap endpoint.

---

## [0.3.0] - 2026-08-09

### Added
- **App Shell & Antarmuka Aplikasi (Tahap 03):**
  - Layout authenticated `Layouts/AppLayout.vue` dan guest `Layouts/GuestLayout.vue`.
  - Komponen sidebar adaptif dengan navigasi berbasis izin akses pengguna (`SidebarNav.php`).
  - Komponen topbar dengan profil pengguna, notifikasi, dan menu logout.

---

## [0.2.0] - 2026-08-09

### Added
- **Autentikasi Breeze Inertia & Manajemen Profil (Tahap 02):**
  - Login, register, forgot password, reset password, dan verifikasi email berbasis Vue 3 (`Pages/Auth/*`).
  - Halaman pengelolaan profil pengguna dan ganti kata sandi (`Pages/Profile/Edit.vue`).

---

## [0.1.0] - 2026-08-09

### Added
- **Inisiasi Fondasi Aplikasi (Tahap 01):**
  - Konfigurasi inti Laravel 13, Inertia.js v3, dan Vue 3.
  - Konfigurasi bundler Vite (`vite.config.js`), Tailwind CSS, dan PostCSS.
  - Setup database MySQL, skema dasar, dan pengujian unit awal via PestPHP.
  - Lisensi MIT dan notice legal hak cipta jejakawan (ADR-013).
