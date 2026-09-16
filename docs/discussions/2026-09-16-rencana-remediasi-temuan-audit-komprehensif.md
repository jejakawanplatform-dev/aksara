# Diskusi: Rencana Remediasi Seluruh Temuan Audit Komprehensif

* **Tanggal Inisiasi:** 2026-09-16
* **Inisiator:** Cursor Agent + Developer
* **Status:** `OPEN`
* **Terkait Dokumen:**
  - [`docs/audit/2026-09-16-audit-independen-dokumentasi-dan-validasi-audit-agen.md`](../audit/2026-09-16-audit-independen-dokumentasi-dan-validasi-audit-agen.md)
  - [`docs/audit/2026-09-16-audit-standar-rekayasa-perangkat-lunak-komprehensif.md`](../audit/2026-09-16-audit-standar-rekayasa-perangkat-lunak-komprehensif.md)
  - [`docs/audit/2026-09-16-laporan-audit-kesenjangan-projek-dan-dokumentasi.md`](../audit/2026-09-16-laporan-audit-kesenjangan-projek-dan-dokumentasi.md)
  - [`docs/steering/coding-standards.md`](../steering/coding-standards.md)
  - [`docs/steering/testing-strategy.md`](../steering/testing-strategy.md)
  - [`docs/steering/business-rules.md`](../steering/business-rules.md)

---

## 1. Konteks & Masalah

Audit independen dan pemeriksaan silang menemukan dua kelompok masalah:

1. Temuan lama yang sudah diperbaiki pada working tree, tetapi laporan audit belum memperbarui statusnya.
2. Temuan aktif yang belum mempunyai rencana eksekusi terpadu, termasuk lima area P0:
   - modal bulk-delete tidak bekerja pada Plans, Materials, dan References;
   - feature flag keamanan tidak menegakkan perilaku;
   - otorisasi update CP/TP/ATP dapat dilewati melalui re-parenting;
   - formula injection pada file spreadsheet;
   - QA release tidak sepenuhnya ditegakkan CI.

Temuan lain mencakup SSRF provider AI, API key plaintext, integritas relasi, transaksi multi-write, import file, aksesibilitas, deployment race, kontrak dokumentasi, bukti QA stale, dan tautan lokal non-portabel.

Tujuan thread ini adalah menyepakati urutan fixing yang:

- mendahulukan risiko eksploitasi dan kehilangan integritas data;
- menghindari perubahan besar dalam satu PR;
- menambahkan test regresi sebelum status temuan ditutup;
- menjaga dokumentasi tetap sinkron dengan implementasi;
- menghasilkan bukti QA yang dapat direproduksi.

### Prinsip pelaksanaan

- Satu workstream besar dipecah menjadi PR kecil dan reviewable.
- Setiap temuan mempunyai ID tetap dan status `open`, `in_progress`, `verified`, atau `accepted_risk`.
- Temuan hanya boleh menjadi `verified` setelah test dan bukti implementasi tersedia.
- Perubahan aturan bisnis wajib disepakati sebelum mengubah kode.
- Perubahan skema wajib melalui migration baru; migration lama tidak diedit.
- Working tree yang ada tidak boleh di-reset atau ditimpa.

---

## 2. Proposal / Usulan Solusi

### Pendekatan

Remediasi dibagi menjadi enam gelombang. Urutan mengikuti dependency dan risiko, bukan urutan file.

## Gelombang 0 — Baseline, keputusan, dan pengamanan pengerjaan

**Tujuan:** membuat baseline reproducible sebelum fixing.

- [ ] `BASE-01` Catat commit SHA/working-tree baseline dan daftar perubahan yang sudah ada.
- [ ] `BASE-02` Buat registry temuan dengan kolom ID, severity, owner, status, PR, test, dan evidence.
- [ ] `BASE-03` Tandai temuan audit lama yang sudah selesai: Pint, dua N+1, transaksi bulk plan, cleanup TipTap, masking API key browser, `$hidden`, throttle AI, dan cache `SettingService`.
- [ ] `BASE-04` Putuskan aturan kanonik akses admin terhadap RPP/materi guru.
- [ ] `BASE-05` Putuskan apakah exception khusus subject `INF` dipertahankan untuk workshop atau dihapus.
- [ ] `BASE-06` Putuskan PHP minimum: 8.3 atau 8.4.
- [ ] `BASE-07` Putuskan kontrak Word: OOXML `.docx` melalui PhpWord atau format kompatibel Word lain.
- [ ] `BASE-08` Putuskan apakah API key wajib encrypted at rest atau risiko plaintext diterima dan didokumentasikan.

**Exit criteria:**

- keputusan BASE-04 sampai BASE-08 tercatat pada thread ini;
- bila keputusan mengubah arsitektur/aturan permanen, ADR ditambah atau diperbarui;
- tidak ada fixing yang bergantung pada asumsi aturan bisnis.

## Gelombang 1 — P0 keamanan dan fungsi pengguna

### 1A. Perbaikan bulk confirmation

- [ ] `FUNC-01` Ubah Plans, Materials, dan References agar memakai `:open`, `confirm-word`, `:processing`, dan `@close`.
- [ ] `FUNC-02` Pertimbangkan dukungan `v-model` resmi pada komponen hanya bila dibutuhkan; jangan mempertahankan dua kontrak tanpa test.
- [ ] `TEST-01` Tambahkan component/E2E test untuk bulk-delete Plans, Materials, Rombel, dan Mapel.
- [ ] `DOC-01` Sinkronkan Spec 18 setelah perilaku terverifikasi.

**Acceptance:**

- modal tampil dari keempat domain;
- tombol confirm terkunci sebelum pengguna mengetik `HAPUS`;
- close, loading, error, selected count, dan select-all-matching bekerja;
- seluruh operasi tetap menjalankan authorization backend.

### 1B. Tutup bypass otorisasi kurikulum

- [ ] `AUTH-01` Pada update CP, authorize subject record asal dan subject tujuan.
- [ ] `AUTH-02` Pada update TP, authorize CP/subject asal dan CP/subject tujuan.
- [ ] `AUTH-03` Pada update ATP, authorize subject/TP asal serta parent tujuan.
- [ ] `AUTH-04` Validasi bahwa TP memang milik subject ATP dan semester milik tahun ajaran yang dipilih.
- [ ] `TEST-02` Tambahkan negative test re-parenting lintas-mapel untuk CP, TP, dan ATP.
- [ ] `TEST-03` Tambahkan test guru tanpa assignment dan guru non-Informatika.

**Acceptance:**

- pengguna tidak dapat membaca ID resource terlarang lalu memindahkannya ke resource yang diizinkan;
- response unauthorized konsisten 403;
- admin mengikuti keputusan BASE-04;
- seluruh relasi parent-child tervalidasi.

### 1C. Cegah formula injection spreadsheet

- [ ] `SEC-01` Ekstrak sanitizer spreadsheet terpusat untuk seluruh nilai string tidak tepercaya.
- [ ] `SEC-02` Terapkan pada export RPP, kurikulum, presensi, pengguna, nama rombel, dan data anak.
- [ ] `SEC-03` Gunakan explicit string cell type bila sesuai agar PhpSpreadsheet tidak menginterpretasikan formula.
- [ ] `TEST-04` Uji prefix `=`, `+`, `-`, `@`, tab, CR, dan payload `HYPERLINK`.
- [ ] `DOC-02` Dokumentasikan aturan keamanan export/import pada business rules dan coding standards.

**Acceptance:**

- payload tidak disimpan sebagai cell type formula;
- nilai normal dan angka bisnis tetap diekspor benar;
- seluruh exporter memakai helper yang sama.

### 1D. Terapkan feature flag keamanan

- [ ] `SEC-04` Terapkan `security.allow_public_registration` pada GET dan POST register.
- [ ] `SEC-05` Gunakan `security.max_login_attempts` pada login throttling.
- [ ] `SEC-06` Terapkan session inactivity timeout.
- [ ] `SEC-07` Terapkan maintenance mode, quiz module, dan parent portal atau tandai sebagai belum tersedia dan hapus kontrol UI semu.
- [ ] `TEST-05` Tambahkan behavioral test untuk nilai on/off setiap flag.

**Acceptance:**

- default registration `false` benar-benar menolak GET/POST;
- perubahan settings langsung memengaruhi perilaku setelah cache invalidation;
- tidak ada kontrol berlabel keamanan yang hanya melakukan persistensi.

## Gelombang 2 — Security hardening dan integritas data

### 2A. Provider AI dan secret

- [ ] `SEC-08` Validasi scheme URL provider; default hanya HTTPS untuk provider jaringan.
- [ ] `SEC-09` Blok loopback, private, link-local, metadata cloud, user-info URL, dan redirect ke target terlarang.
- [ ] `SEC-10` Gunakan allowlist host untuk vendor bawaan; kebijakan vendor custom harus eksplisit.
- [ ] `SEC-11` Jangan kirim exception mentah ke browser atau log yang dapat memuat secret.
- [ ] `SEC-12` Hindari API key pada query string bila vendor mendukung header.
- [ ] `SEC-13` Implementasikan encrypted cast/migration key atau koreksi klaim dokumentasi sesuai BASE-08.
- [ ] `TEST-06` Tambahkan test SSRF, redirect, DNS/private IP, masking, serialization, dan error redaction.

### 2B. Integritas identitas dan relasi

- [ ] `DATA-01` Validasi hanya akun role student yang dapat menjadi anggota rombel.
- [ ] `DATA-02` Validasi hanya role teacher/homeroom yang sesuai dapat menjadi guru mapel/wali kelas.
- [ ] `DATA-03` Validasi konsistensi academic year, semester, class, subject, CP, dan TP pada Plan.
- [ ] `DATA-04` Tegakkan one-to-one `learning_plans` → `learning_materials` dengan unique index baru.
- [ ] `DATA-05` Selaraskan guard bulk rombel: lindungi anggota aktif atau koreksi dokumentasi bila detach-and-delete memang keputusan bisnis.
- [ ] `DATA-06` Putuskan dan tegakkan lifecycle event `material_read`; jangan dokumentasikan event yang tidak pernah diproduksi.
- [ ] `TEST-07` Tambahkan test role mismatch, cross-parent mismatch, race material creation, dan deletion guard.

### 2C. Atomisitas multi-write

- [ ] `TX-01` Bungkus save presensi batch dalam transaction.
- [ ] `TX-02` Bungkus approve draft (generation, plan, material) dalam transaction.
- [ ] `TX-03` Bungkus publish plan + material dalam transaction.
- [ ] `TX-04` Bungkus penetapan wali kelas dan aktivasi tahun/semester dalam transaction.
- [ ] `TEST-08` Simulasikan exception di tengah proses dan pastikan rollback penuh.

### 2D. Import file dan error handling

- [ ] `IMP-01` Tambahkan MIME/signature validation pada import referensi.
- [ ] `IMP-02` Tangani file rusak sebelum `IOFactory::load()` menghasilkan 500.
- [ ] `IMP-03` Batasi jumlah row, worksheet, shared strings, dan ukuran hasil dekompresi.
- [ ] `IMP-04` Hindari `toArray()` penuh untuk file besar; gunakan chunk/read filter bila relevan.
- [ ] `IMP-05` Ganti semua response `getMessage()` dengan pesan aman dan correlation ID.
- [ ] `TEST-09` Uji corrupt XLSX, zip bomb-like limits, MIME spoof, oversized rows, dan exception redaction.

## Gelombang 3 — CI, E2E, dan deployment

### 3A. QA gate yang benar-benar enforced

- [ ] `CI-01` Tambahkan Pint ke CI.
- [ ] `CI-02` Tambahkan npm install/cache, Vitest, dan Vite build ke CI.
- [ ] `CI-03` Tambahkan Playwright Chromium dan E2E ke CI.
- [ ] `CI-04` Simpan trace, screenshot, dan report E2E ketika gagal.
- [ ] `CI-05` Jalankan doc lint untuk broken link, URI lokal, artifact hilang, dan metadata.
- [ ] `CI-06` Jadikan deploy bergantung pada seluruh gate release wajib.

### 3B. Database test yang sesuai kontrak

- [ ] `CI-07` Pastikan Pest CI benar-benar memakai MySQL, bukan override SQLite dari `phpunit.xml`.
- [ ] `CI-08` Cetak driver/database aktif sebelum test.
- [ ] `CI-09` Pertahankan SQLite sebagai fast local lane bila diinginkan, tetapi tambahkan MySQL integration lane.
- [ ] `TEST-10` Tambahkan test constraint, transaction, collation, dan concurrency kritis pada MySQL.

### 3C. Playwright reproducible

- [ ] `E2E-01` Tambahkan langkah `playwright install --with-deps chromium`.
- [ ] `E2E-02` Konfigurasi `webServer` atau dokumentasikan startup server yang otomatis.
- [ ] `E2E-03` Dokumentasikan migration/seed, base URL, fixture account, dan cleanup state.
- [ ] `E2E-04` Tambahkan coverage modal bulk untuk seluruh domain, settings flag, TipTap dialog, dan alur permission negatif.

### 3D. Deploy immutable dan aman

- [ ] `DEP-01` Checkout `github.event.workflow_run.head_sha`.
- [ ] `DEP-02` Deploy artifact/commit yang sama dengan commit teruji.
- [ ] `DEP-03` Hilangkan jendela kode-baru/skema-lama dengan release phase atau migration backward-compatible.
- [ ] `DEP-04` Tambahkan post-deploy smoke dan strategi rollback berbasis artifact + DB snapshot.

## Gelombang 4 — Frontend, aksesibilitas, dan performa

### 4A. Navigasi dan lifecycle

- [ ] `FE-01` Bedakan tautan internal Inertia dari download/external pada `Btn.vue`.
- [ ] `FE-02` Bersihkan timer debounce saat unmount pada Plans, Materials, Users, dan Evaluation Monitoring.
- [ ] `TEST-11` Tambahkan test bahwa navigasi internal tidak hard reload dan debounce tidak berjalan setelah unmount.

### 4B. Aksesibilitas

- [ ] `A11Y-01` Implementasikan initial focus, focus trap, Escape, dan restore focus pada Modal.
- [ ] `A11Y-02` Tambahkan `role="dialog"`, `aria-modal`, accessible name, dan keyboard handling pada dialog rumus TipTap.
- [ ] `A11Y-03` Ubah header grup Sidebar menjadi button yang keyboard-accessible.
- [ ] `A11Y-04` Tambahkan `aria-expanded` dan `aria-controls` pada kontrol collapse.
- [ ] `A11Y-05` Audit hubungan label/input, contrast, error announcement, dan target keyboard.
- [ ] `TEST-12` Jalankan axe + Playwright keyboard flow untuk halaman kritis.

### 4C. Database dan bundle performance

- [ ] `PERF-01` Validasi index redundan attendance dan quiz dengan `SHOW INDEX`/`EXPLAIN`.
- [ ] `PERF-02` Buat migration penghapusan index hanya jika bukti workload mendukung.
- [ ] `PERF-03` Tetapkan budget bundle untuk TipTap, KaTeX, initial JS, dan route chunks.
- [ ] `PERF-04` Uji load/concurrency kuis dengan target tunggal yang disepakati, bukan 30+ dan 100+ sekaligus.
- [ ] `PERF-05` Ukur biaya empat kali filter koleksi status presensi; gunakan agregasi satu lintasan hanya bila hasil profiling membenarkan perubahan.

## Gelombang 5 — Harmonisasi dokumentasi dan governance

### 5A. Source of truth dan kontrak

- [ ] `DOC-03` Sinkronkan `database-schema.md`, khususnya `ai_providers`.
- [ ] `DOC-04` Lengkapi `api-contract.md`, termasuk `plans.open-material`, route relasi user, route provider, section aliases, permission, throttle, dan path media.
- [ ] `DOC-05` Sinkronkan business rules admin sesuai BASE-04.
- [ ] `DOC-06` Sinkronkan scope subject `INF` sesuai BASE-05.
- [ ] `DOC-07` Sinkronkan PHP minimum sesuai BASE-06.
- [ ] `DOC-08` Sinkronkan kontrak Word sesuai BASE-07.
- [ ] `DOC-09` Koreksi Vite 7 menjadi versi aktual dan hindari hard-code versi yang tidak perlu.
- [ ] `DOC-10` Koreksi diagram route kuis Spec 19 dan nama Tahap 14 pada indeks.

### 5B. QA evidence dan PHPStan

- [ ] `DOC-11` Hapus angka QA 131/145/146/193 yang tampil sebagai status aktif.
- [ ] `DOC-12` Catat baseline baru dengan tanggal, commit SHA, runtime, database, dan link CI.
- [ ] `DOC-13` Hapus referensi `phpstan-baseline.neon` karena file tidak ada, atau tambahkan file hanya bila benar-benar diperlukan.
- [ ] `DOC-14` Dokumentasikan exclusion `EnsureRole.php` sebagai debt atau perbaiki lalu hapus exclusion.
- [ ] `DOC-15` Hindari klaim “Level 9 maksimum/murni” tanpa konteks versi dan exclusion.
- [ ] `DOC-16` Bedakan hasil historis dari acceptance criteria pada semua `verification.md`.

### 5C. Konsistensi spec/discussion

- [ ] `DOC-17` Lengkapi verification Spec 05, 06, 08, 09, 18, dan 19.
- [ ] `DOC-18` Ubah status plan ambigu `selesai / aktif` menjadi satu nilai enum.
- [ ] `DOC-19` Samakan kosakata debt `todo` vs `deferred`.
- [ ] `DOC-20` Tandai bagian pra-adopsi pada thread lama sebagai baseline historis.
- [ ] `DOC-21` Selaraskan checklist thread ADOPTED dengan outcome implementasi.
- [ ] `DOC-22` Lengkapi bagian scope/uji manual yang hilang sesuai template.
- [ ] `DOC-23` Urutkan ADR atau tambah indeks ADR numerik tanpa mengubah sejarah keputusan.

### 5D. Portabilitas dan audit governance

- [ ] `DOC-24` Ganti seluruh 31 URI `file:///c:/Users/...` menjadi link relatif.
- [ ] `DOC-25` Tetapkan owner, `last_reviewed_at`, dan `verified_commit` pada dokumen kanonik.
- [ ] `DOC-26` Definisikan rubric audit sebelum memakai skor/persentase.
- [ ] `DOC-27` Tandai setiap temuan audit sebagai open/fixed/accepted-risk dengan bukti.
- [ ] `DOC-28` Koreksi klaim “100% parity” menjadi status berbasis checklist yang terukur.
- [ ] `DOC-29` Sinkronkan target load test menjadi satu angka dan environment yang jelas.

## Gelombang 6 — Maintainability debt

- [ ] `BE-01` Ekstrak validasi payload kompleks ke FormRequest secara bertahap, dimulai dari Plan dan Reference mutation.
- [ ] `BE-02` Tambahkan structured logging pada failover/provider AI tanpa secret.
- [ ] `BE-03` Hindari refactor massal controller sebelum P0/P1 selesai dan teruji.
- [ ] `TEST-13` Pertahankan coverage behavior saat memindahkan validasi.

---

## 3. Strategi PR dan Urutan Merge

Usulan pemecahan agar review tidak terlalu besar:

1. **PR-01:** BulkConfirmModal contract + regression tests.
2. **PR-02:** Authorization re-parenting CP/TP/ATP + negative tests.
3. **PR-03:** Spreadsheet formula sanitization + export tests.
4. **PR-04:** Registration/login/security flag enforcement.
5. **PR-05:** AI provider SSRF, secret encryption/redaction.
6. **PR-06:** Relational integrity + one-to-one migration.
7. **PR-07:** Transactions + importer hardening.
8. **PR-08:** CI frontend/Pint/build + MySQL lane.
9. **PR-09:** Playwright reproducibility + E2E expansion.
10. **PR-10:** Immutable deploy and migration sequencing.
11. **PR-11:** Accessibility, Btn navigation, and debounce cleanup.
12. **PR-12:** Documentation harmonization and doc lint.
13. **PR-13:** Index/load/bundle measurements and justified optimizations.
14. **PR-14:** FormRequest/logging maintainability cleanup.

Setiap PR harus:

- menyebut ID temuan yang ditutup;
- tidak mencampur perubahan tanpa dependency;
- menambahkan test regresi;
- memperbarui spec/verification terkait;
- menyertakan hasil QA dan commit SHA.

---

## 4. Risiko & Trade-off

### Risiko

- Enkripsi API key memerlukan migrasi data lama dan strategi rotasi bila decrypt gagal.
- Menonaktifkan registrasi dapat mengubah alur demo/workshop.
- Pengetatan relasi dapat menolak data legacy yang sebelumnya diterima.
- Menjalankan Playwright/MySQL di CI menambah durasi dan biaya.
- Unique index material dapat gagal bila database lama sudah memiliki duplikasi.
- Migration sebelum/bersama deploy harus dirancang backward-compatible.

### Mitigasi

- Tambahkan preflight query dan backup sebelum migration data.
- Pisahkan mode demo dari production policy secara eksplisit.
- Sediakan report dry-run untuk data legacy invalid.
- Gunakan cache dependency/browser dan parallel CI jobs.
- Bersihkan duplikasi dengan migration terukur sebelum unique constraint.
- Terapkan expand-and-contract migration untuk perubahan skema berisiko.

---

## 5. Acceptance Global dan QA

Semua workstream dianggap selesai hanya jika:

- [ ] Tidak ada P0/P1 berstatus `open` tanpa keputusan accepted-risk tertulis.
- [ ] `php artisan test` lulus pada MySQL CI dan local fast lane.
- [ ] `vendor/bin/phpstan analyse --memory-limit=1G` lulus; exclusion tercatat.
- [ ] `vendor/bin/pint --test` lulus.
- [ ] `npm run test:unit` lulus.
- [ ] `npm run build` lulus sesuai warning policy yang disepakati.
- [ ] `npm run test:e2e` reproducible dan lulus di CI.
- [ ] Security regression tests mencakup IDOR, SSRF, formula injection, secret redaction, dan feature flags.
- [ ] Aksesibilitas kritis lolos keyboard flow dan axe.
- [ ] Dokumentasi tidak mempunyai link lokal absolut atau artefak fiktif.
- [ ] Hasil QA mencantumkan tanggal, SHA, runtime, DB, dan link run/artifact.
- [ ] Spec terkait diperbarui dan handover mencatat hasil akhir.

---

## 6. Tanggapan & Tinjauan Alternatif

### Pertanyaan untuk reviewer/developer

1. Apakah admin boleh mengubah/menghapus RPP dan materi semua guru?
2. Apakah bypass subject `INF` masih dibutuhkan untuk workshop?
3. Apakah PHP 8.4 menjadi minimum resmi?
4. Apakah Word wajib `.docx` OOXML?
5. Apakah API key wajib dienkripsi di database?
6. Apakah seluruh feature flag akan diaktifkan sekarang, atau kontrol yang belum aktif dihapus dari UI?
7. Apakah MySQL E2E/integration wajib pada setiap PR atau hanya main/release?
8. Berapa target load test kuis yang realistis untuk environment produksi?

### Tanggapan dari reviewer

* **Pandangan:** Belum diisi.
* **Argumen / Analisis:** Belum diisi.
* **Alternatif:** Belum diisi.

---

## 7. Konsensus & Keputusan Akhir

*(Diisi setelah pertanyaan keputusan disepakati.)*

* **Solusi Terpilih:** Belum ditetapkan.
* **Rencana Tindak Lanjut:**
  1. [ ] Jawab keputusan BASE-04 sampai BASE-08.
  2. [ ] Ubah status thread menjadi `CONSENSUS`.
  3. [ ] Buat issue/PR per urutan merge.
  4. [ ] Update spec dan ADR yang terdampak.
  5. [ ] Implementasikan Gelombang 1 sebelum workstream risiko lebih rendah.
  6. [ ] Tutup setiap temuan dengan test dan evidence.
  7. [ ] Setelah seluruh acceptance global terpenuhi, update handover dan ubah status menjadi `ADOPTED`.
