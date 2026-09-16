# Diskusi: Evaluasi Kesenjangan Test Suite & Perancangan Smoke Test Otomatis

* **Tanggal Inisiasi:** 2026-09-15
* **Inisiator:** Antigravity AI (Pair Programming Assistant) & Developer
* **Status:** `ADOPTED` (Pest Critical Journey Smoke Test + PHPStan Level 9 Diadopsi & Diimplementasikan)
* **Terkait Dokumen:**
  - `docs/steering/coding-standards.md`
  - `docs/steering/testing-strategy.md`
  - `docs/steering/handover.md`
  - `tests/Feature/Smoke/CriticalJourneySmokeTest.php`

---

## 1. Konteks & Analisis Kondisi Saat Ini

Aksara saat ini memiliki fondasi backend testing yang sangat solid:
* **145 Feature & Unit Tests** via PestPHP (954 assertions) dengan status 100% lolos (*green*).
* **Static Analysis:** PHPStan / Larastan Level 5 lolos (0 errors).
* **Asset Bundler:** Vite build stabil tanpa warning kompilasi.

Namun, mengacu pada bagian *"Yang sengaja ditunda"* di [testing-strategy.md](../steering/testing-strategy.md) dan catatan *"Perlu penguatan"* di [handover.md](../steering/handover.md), proyek ini masih memiliki beberapa **kesenjangan pengujian (test suite gaps)** yang berisiko menimbulkan regresi tak terdeteksi.

---

## 2. Peta Kesenjangan (Test Suite Gaps) yang Belum Dimiliki

```text
[Lengkap]   Backend Feature Tests (Pest HTTP Request, Auth, Permission, Controller)
[Lengkap]   Static Analysis (PHPStan Level 5)
    ─── KESENJANGAN (GAPS) ───
[Belum Ada] 1. Critical Journey Smoke Test (Uji end-to-end 5 role dalam 1 skenario cepat)
[Belum Ada] 2. Browser E2E / Smoke Test (Playwright/Dusk untuk TipTap, KaTeX, & Vue UI)
[Belum Ada] 3. Frontend Unit Tests (Vitest untuk composables & komponen UI)
[Belum Ada] 4. AI Resilience & Fallback Tests (Timeout, parse error, mock fallback)
[Belum Ada] 5. Concurrency / Load Stress Test (30+ siswa submit kuis bersamaan)
```

### Rincian Kesenjangan:

### 🔴 Gap 1: Ketiadaan Critical Journey Smoke Test (End-to-End Pipeline)
* **Masalah:** Saat ini pengujian berjalan modular per Controller (misal `UserManagementTest`, `CreatePlanTpTest`, `MaterialAuthoringTest`). Belum ada satu test cepat (*smoke test*) yang mensimulasikan **siklus hidup lengkap sekolah** secara bersambung:
  1. Guru buat RPP ➔ 2. AI generate materi ➔ 3. Guru publish ➔ 4. Siswa baca & kerjakan kuis ➔ 5. Guru input absensi ➔ 6. Wali kelas & Ortu melihat rekap.
* **Risiko:** Jika ada perubahan relasi atau shared props di `HandleInertiaRequests`, alur bersambung antar role bisa putus tanpa terdeteksi unit test terisolasi.

### 🔴 Gap 2: Ketiadaan Browser / Headless E2E Smoke Test (TipTap & Rich UI)
* **Masalah:** Interaksi frontend kompleks saat ini **100% masih bergantung pada uji manual**:
  * Rich text editor TipTap (bold, heading, table, list).
  * MediaPicker (upload gambar lokal, thumbnail preview, resize properti gambar).
  * Render rumus matematika KaTeX (`\frac{a}{b}`).
  * Modal konfirmasi ketik `"HAPUS"` pada Bulk Actions.
* **Risiko:** Regresi UI di JavaScript/Vue tidak terdeteksi oleh `php artisan test` (Pest hanya mengetes output JSON Inertia, bukan rendering DOM di browser).

### 🟡 Gap 3: Ketiadaan Frontend Unit Testing (Vitest)
* **Masalah:** Logika frontend yang kompleks seperti `useBulkSelect.js`, filter client-side, dan formatter nilai kuis belum memiliki unit test di sisi JavaScript (`package.json` belum memiliki script test JS).

### 🟡 Gap 4: AI Provider Resilience & Fallback Simulation
* **Masalah:** Pengujian AI saat ini baru mencakup `AI_MOCK_MODE=true`. Belum ada test untuk skenario:
  * Provider API OpenAI / Gemini mengembalikan error 429 (Rate Limit) atau 500.
  * Respon AI JSON rusak / terpotong (*malformed markdown*).
  * Validasi fallback otomatis ke provider sekunder.

---

## 3. Proposal Pendekatan Solusi

Kami mengusulkan strategi bertahap (*phased rollout*) berdasarkan *Return on Investment* (kecepatan implementasi vs perlindungan regresi):

### 🌟 Solusi A (Prioritas 1): Pest Critical Journey Smoke Test
* **Deskripsi:** Membuat file test khusus: `tests/Feature/Smoke/CriticalJourneySmokeTest.php`.
* **Kelebihan:**
  * **Zero Dependency Baru:** Menggunakan PestPHP & DB SQLite/MySQL yang sudah ada.
  * **Super Cepat:** Berjalan di bawah 3 detik.
  * **Langsung Aktif di CI:** Langsung terlindungi di GitHub Actions tanpa setup headless browser.
* **Cakupan Alur:**
  ```text
  Admin (cek user & kuota) 
    ➔ Guru (buat RPP & materi) 
    ➔ Siswa (akses materi & submit kuis bernilai 100) 
    ➔ Guru (input hadir & evaluasi) 
    ➔ Wali Kelas (buka rekap 100% hadir) 
    ➔ Wali Murid (lihat nilai kuis 100)
  ```

### 🌟 Solusi B (Prioritas 2): Browser Smoke Test Ringan via Playwright
* **Deskripsi:** Menambahkan Playwright (`@playwright/test`) di `package.json` untuk 3 smoke test browser kritis:
  1. Login form & navigasi sidebar per role.
  2. TipTap Editor: mengetik, format teks, dan preview rumus KaTeX.
  3. Bulk Actions: centang checkbox, muncul toolbar, ketik `"HAPUS"` pada modal.
* **Kelebihan:** Memberikan kepastian 100% bahwa halaman benar-benar bisa diklik dan dirender pengguna nyata.

### 🌟 Solusi C (Prioritas 3): Vitest untuk Composable & Helpers
* **Deskripsi:** Memasang Vitest untuk menguji composables (`useBulkSelect`, `useCan`, `useFlash`) secara headless tanpa membebani browser.

---

## 4. Matriks Perbandingan Opsi

| Kriteria | Solusi A (Pest Journey Smoke) | Solusi B (Playwright Browser E2E) | Solusi C (Vitest Frontend Unit) |
| :--- | :---: | :---: | :---: |
| **Effort Pembuatan** | Rendah (1 file PHP) | Sedang (setup runner + spec) | Rendah–Sedang |
| **Kecepatan Eksekusi** | ⚡ Sangat Cepat (<3s) | ⏳ Sedang (10–25s) | ⚡ Cepat (<2s) |
| **Dukungan CI Otomatis** | Langsung Siap (Out-of-the-box) | Butuh setup Chromium di CI | Langsung Siap |
| **Area yang Dilindungi** | Logika bisnis & relasi 5 role | Render DOM, TipTap, KaTeX, UX | Logika JS reaktif murni |

---

## 5. Pertanyaan Terbuka untuk Konsensus Tim

1. **Prioritas Awal:** Apakah disepakati untuk memulai dari **Solusi A (Pest Critical Journey Smoke Test)** terlebih dahulu karena langsung memberikan rasa aman pada alur bisnis 5 role tanpa perlu menambah dependency eksternal?
2. **Framework Browser E2E:** Untuk tahap browser testing (Solusi B), apakah sepakat menggunakan **Playwright** (standar modern untuk Vite/Vue 3) dibanding Laravel Dusk?
3. **Skenario Kritis:** Di luar 5 role di atas, apakah ada alur khusus yang paling sering bermasalah saat workshop Bimtek yang perlu diprioritaskan dalam smoke test (misal: mode offline tanpa AI API / import Excel referensi)?

---

## 6. Rencana Tindak Lanjut & Hasil Adopsi (Next Steps)

* [x] Sepakati pilihan solusi dan urutan prioritas di bagian 5 (Solusi A diimplementasikan terlebih dahulu).
* [x] Buat file pengujian `tests/Feature/Smoke/CriticalJourneySmokeTest.php` mencakup alur 5 role terpadu.
* [x] Jalankan dan verifikasi kelulusan skenario journey smoke test (136 assertions lolos 100%).
* [x] Naikkan PHPStan ke Level 9 dengan 0 errors / 0 warnings.
* [x] Dokumentasikan protokol QA imperatif di `docs/steering/coding-standards.md`, `testing-strategy.md`, dan `handover.md`.
* [x] Update status thread diskusi menjadi `ADOPTED`.
