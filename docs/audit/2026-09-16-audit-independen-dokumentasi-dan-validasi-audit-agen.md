# Audit Independen Dokumentasi dan Validasi Audit Agen — Aksara

**Tanggal:** 16 September 2026  
**Baseline:** working tree lokal saat audit, termasuk perubahan yang belum di-commit  
**Cakupan:** `docs/`, kontrak implementasi yang dirujuk dokumentasi, konfigurasi QA/CI, dan dua laporan audit lain pada `docs/audit/`  
**Metode:** inventarisasi 103 artefak dokumentasi, pemeriksaan silang statis, verifikasi konfigurasi, dan eksekusi QA lokal

## Ringkasan eksekutif

Dokumentasi Aksara memiliki struktur yang kuat: 19 tahap spesifikasi masing-masing mempunyai `plan.md`, `tasks.md`, `implementation.md`, dan `verification.md`; steering, ADR, diskusi, dan handover juga tersedia. Perubahan terbaru telah menutup banyak gap implementasi yang ditemukan audit agen lain.

Namun, kesimpulan audit agen lain bahwa proyek telah mencapai **100% keselarasan** tidak dapat dipertahankan. Audit ini menemukan gap material pada penegakan QA, bukti verifikasi, kontrak skema/rute, keamanan konfigurasi AI, portabilitas tautan, dan tata kelola status dokumen. Skor persentase pada laporan lama juga tidak mempunyai rumus, denominator, atau daftar kontrol yang dapat direproduksi.

Status keseluruhan:

- **Kelengkapan struktur:** baik.
- **Keselarasan implementasi:** cukup baik, tetapi belum 100%.
- **Keandalan bukti QA:** sedang; hasil lokal kuat, rekaman dokumen stale.
- **Penegakan otomatis:** lemah; CI hanya menegakkan sebagian gate yang dinyatakan wajib.
- **Keamanan dokumentasi konfigurasi AI:** perlu koreksi segera.

## Baseline verifikasi aktual

Perintah dijalankan pada working tree yang sama dengan audit:

- `php artisan test`: **196 tests passed, 1.318 assertions**.
- `vendor/bin/phpstan analyse --memory-limit=1G`: **passed, 0 errors**.
- `vendor/bin/pint --test`: **passed**.
- `npm run test:unit`: **14/14 tests passed** pada 3 file.
- `npm run build`: **passed** dalam 6,42 detik, tetapi menghasilkan peringatan `PLUGIN_TIMINGS`; klaim “tanpa warning” tidak tepat.
- `npm run test:e2e`: **belum tervalidasi**. Seluruh 21 test tidak dapat memulai Chromium karena binary Playwright belum terpasang. Ini kegagalan environment, bukan kegagalan assertion aplikasi.

Hasil ini menggantikan angka 131, 146, dan 193 test yang tersebar pada dokumentasi sebagai baseline lokal terbaru. Angka dinamis sebaiknya selalu disertai tanggal, commit SHA, environment, dan output CI.

## Temuan prioritas

### P0 — FUNC-01: Modal konfirmasi bulk-delete rusak pada Plans, Materials, dan References

Dokumentasi Spec 18 menyatakan `BulkConfirmModal` dengan guard kata `"HAPUS"` aktif pada empat domain. Kontrak komponen aktual menerima `open`, `confirmWord`, `processing`, serta emit `close`/`confirm`.

Hanya `Users/Index.vue` menggunakan kontrak tersebut. Tiga halaman lain memakai:

- `v-model` alih-alih `:open` + `@close`;
- `danger-word` alih-alih `confirm-word`;
- `loading` alih-alih `processing`.

Akibatnya prop `open` tetap `false`, kata guard kosong, dan modal tidak bekerja sesuai dokumentasi. E2E bulk action hanya menguji Users sehingga regresi tiga domain tidak tertangkap.

**Rekomendasi:** samakan pemakaian prop/event pada ketiga halaman dan tambahkan component/E2E test untuk Plans, Materials, Rombel, dan Mapel.

### P0 — SEC-03: Feature flag keamanan hanya disimpan, tidak diterapkan

Halaman settings menyimpan `security.allow_public_registration`, `security.session_timeout_minutes`, `security.max_login_attempts`, maintenance mode, quiz module, dan parent portal. Pemeriksaan silang implementasi menunjukkan nilai tersebut tidak menegakkan perilaku:

- route GET/POST `/register` selalu aktif;
- login limit tetap hard-coded `5`;
- flag session timeout, maintenance, quiz, dan parent portal hanya tampil/tersimpan.

Dengan default dokumenter `security.allow_public_registration=false`, pengguna tetap dapat membuka registrasi publik dan membuat akun.

**Rekomendasi:** implementasikan enforcement di middleware/controller/routing dan tambahkan behavioral tests. Sampai itu dilakukan, tandai flag sebagai UI placeholder, bukan kontrol keamanan aktif.

### P0 — AUTH-01: Otorisasi update kurikulum dapat dilewati melalui re-parenting

`ReferenceController::updateCp()` memeriksa izin terhadap `subject_id` tujuan, tetapi tidak terhadap subject record CP saat ini. Pola yang sama ada pada update TP/ATP yang memeriksa parent tujuan.

Guru yang hanya boleh mengelola mapel A dapat mengirim ID record mapel B lalu memindahkan sekaligus mengubah record tersebut ke mapel A. Tidak ada negative test lintas-mapel yang menutup skenario ini.

**Rekomendasi:** authorize resource asal sebelum menerima perubahan parent, lalu authorize parent tujuan; bungkus perubahan relasional dalam policy/FormRequest dan tambahkan test IDOR lintas-mapel.

### P0 — SEC-04: Formula injection pada ekspor spreadsheet

Beberapa exporter menulis data pengguna/kurikulum langsung dengan `setCellValue()`, termasuk topik, tujuan, referensi, nama siswa, CP, dan TP. Nilai yang diawali `=`, `+`, `-`, atau `@` dapat diperlakukan spreadsheet client sebagai formula.

`UserExportImportService` sudah mempunyai pola sanitasi parsial, tetapi pola tersebut belum diterapkan konsisten pada export RPP, kurikulum, presensi, rombel, dan data anak.

**Rekomendasi:** gunakan satu sanitizer spreadsheet terpusat atau tulis string sebagai tipe eksplisit; tambahkan test payload `=HYPERLINK(...)`, `+cmd`, `-1+1`, dan `@SUM(...)`.

### P0 — QA-01: Gate wajib tidak ditegakkan oleh CI dan deploy

Dokumen `docs/steering/coding-standards.md` dan `docs/steering/testing-strategy.md` mewajibkan Pest, smoke test, Vitest, Playwright, PHPStan, Pint, dan Vite build. Namun `.github/workflows/ci.yml` hanya menjalankan:

1. migrasi/seed dan Pest;
2. PHPStan.

CI tidak menjalankan Vitest, Playwright, Pint, atau build frontend. `.github/workflows/deploy.yml` akan melakukan deploy setelah workflow CI tersebut sukses. Dengan demikian, perubahan frontend yang gagal build, format PHP yang melanggar standar, atau E2E yang rusak tetap dapat lolos ke deploy.

**Rekomendasi:** jadikan Pint, Vitest, build, dan Playwright sebagai job CI atau nyatakan secara eksplisit mana gate lokal dan mana gate CI. Deploy harus bergantung pada seluruh gate release yang wajib.

### P1 — CI-01: CI terlihat memakai MySQL, tetapi test dikunci ke SQLite memory

Workflow CI menyalakan MySQL 8.4 dan menjalankan migration/seed terhadapnya. Namun `phpunit.xml` menetapkan `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:`, sehingga proses Pest kemungkinan menimpa konfigurasi `.env` dan menjalankan test aplikasi terhadap SQLite.

**Dampak:** perbedaan MySQL pada collation, constraint, locking, transaksi, dan concurrency tidak tercakup walaupun dokumentasi menyatakan CI MySQL.

**Rekomendasi:** gunakan konfigurasi PHPUnit khusus CI yang benar-benar menunjuk MySQL dan tampilkan koneksi aktif sebagai langkah diagnostik sebelum test.

### P1 — DEPLOY-01: Deploy tidak mengunci commit yang sudah diuji

Deploy dipicu oleh `workflow_run`, tetapi checkout tidak memakai `github.event.workflow_run.head_sha`. Bila `main` bergerak setelah CI selesai, workflow dapat mengambil HEAD baru yang belum menjadi subjek run sukses tersebut.

Selain itu `railway up` dilakukan sebelum migration, menciptakan jendela ketika kode baru dapat berjalan terhadap skema lama.

**Rekomendasi:** checkout SHA dari event CI, deploy artifact/commit immutable, dan gunakan urutan migrasi yang kompatibel mundur atau release phase yang atomik.

### P1 — DOC-01: Bukti QA saling bertentangan dan stale

Contoh:

- `docs/steering/handover.md` menyatakan 193 tests / 1.287 assertions, tetapi pada bagian lain menyatakan “terakhir: 131 passed”.
- `docs/spec/19-smoke-testing-qa/verification.md` dan `tasks.md` masih menyatakan 146 tests / 1.090 assertions.
- Audit gap agen lain menyatakan 193/1.287 sebagai “status terkini”.
- Baseline aktual adalah 196/1.318.

**Dampak:** pembaca tidak dapat menentukan hasil QA yang kanonik.

**Rekomendasi:** simpan hasil run di satu registry bertanggal dan bertaut commit/CI; pada spec gunakan “lihat run terbaru” daripada menyalin angka yang cepat usang.

### P1 — DOC-02: Artefak `phpstan-baseline.neon` tidak ada

`docs/README.md`, `docs/steering/handover.md`, ADR-016, dan seluruh Spec 19 berulang kali menyatakan adanya `phpstan-baseline.neon`. File tersebut tidak ditemukan dan `phpstan.neon` juga tidak meng-include baseline.

Selain itu `phpstan.neon` mengecualikan `app/Http/Middleware/EnsureRole.php`, sehingga frasa “seluruh aplikasi”, “murni”, atau “tanpa pengecualian” tidak akurat. Klaim bahwa level 9 adalah level maksimum juga harus disesuaikan dengan versi PHPStan yang digunakan, bukan dinyatakan sebagai fakta abadi.

**Rekomendasi:** hapus semua referensi baseline bila memang sengaja tidak digunakan; dokumentasikan pengecualian `EnsureRole.php` sebagai debt atau perbaiki lalu hapus exclusion.

### P1 — QA-02: Prosedur Playwright tidak reproducible

Dokumentasi hanya memberi `npm run test:e2e`, sementara:

- tidak ada langkah `npx playwright install` atau instalasi browser setara;
- `playwright.config.js` tidak mempunyai `webServer`;
- perintah tidak menyalakan `php artisan serve`;
- CI tidak menjalankan Playwright.

Klaim “Playwright pass” pada handover tidak dilengkapi tautan run/artifact dan tidak dapat direproduksi dari setup yang didokumentasikan.

**Rekomendasi:** dokumentasikan bootstrap database/seed, server, browser install, base URL, dan akun fixture; idealnya gunakan `webServer` serta job CI dengan artifact trace/screenshot.

### P1 — SEC-01: Dokumentasi menyatakan API key terenkripsi, implementasi menyimpan plaintext

`docs/steering/deployment.md` menyebut key pada `ai_providers` “terenkripsi/tersimpan DB admin”. Model `AiProvider` hanya menyembunyikan `api_key` saat serialisasi; migration memakai kolom `text`, tanpa cast `encrypted` atau enkripsi eksplisit.

Masking ke browser dan `$hidden` yang baru ditambahkan adalah perbaikan penting, tetapi tidak mengenkripsi data at rest.

**Rekomendasi:** pilih salah satu:

1. implementasikan encrypted cast/penyimpanan rahasia dan migrasi nilai lama; atau
2. koreksi dokumentasi menjadi “plaintext di DB, dibatasi akses aplikasi”, lalu nyatakan risiko residual.

### P1 — SEC-02: Blind spot audit lama pada test koneksi provider AI

`SettingsController::testConnection()` menerima `base_url` dari admin lalu server melakukan HTTP request. Tidak ada pembatasan scheme/host/private network yang terlihat, sehingga endpoint ini merupakan permukaan SSRF administratif. Untuk Gemini, API key ditempatkan pada query string; pesan exception dikirim kembali ke browser dan berpotensi membawa detail URL/secret.

**Rekomendasi:** validasi HTTPS, terapkan allowlist vendor atau blok alamat private/link-local, jangan mengembalikan exception mentah, dan hindari secret pada URL bila API vendor memungkinkan.

### P1 — DOC-03: Kontrak database `ai_providers` salah

`docs/steering/database-schema.md` menyebut kolom `driver`. Migration dan model menggunakan `vendor_key`, serta mempunyai kolom penting lain seperti `max_tokens`, `temperature`, `timeout_seconds`, `custom_headers`, dan `is_custom`.

**Rekomendasi:** sinkronkan tabel skema dengan migration aktual dan jelaskan status enkripsi setiap field rahasia.

### P1 — DOC-04: API contract mengklaim permukaan andal tetapi tidak lengkap

`docs/steering/api-contract.md` belum mencatat beberapa route secara eksplisit, antara lain:

- `plans.open-material`;
- alias section referensi `references.section.school`, `references.section.academic`, dan `references.section.curriculum`;
- route relasi pengguna (`attach-class`, `detach-class`, `attach-child`, `detach-child`, `homeroom`);
- rincian endpoint provider termasuk `providers.test`, toggle, dan priority.

Kontrak mencatat media sebagai `public/materials/{id}/`, sedangkan implementasi/Spec 16 menggunakan disk public pada `storage/app/public/materials/{id}/` dengan URL `/storage/materials/...`.

**Rekomendasi:** tentukan apakah dokumen adalah daftar lengkap atau ringkasan. Bila kontrak, generate/cek daftar route terhadap `route:list` dan hindari wildcard “CRUD/import” yang menyembunyikan guard penting.

### P1 — RULE-01: Aturan admin bertentangan dengan implementasi dan spec

`docs/steering/business-rules.md` menyatakan admin secara default tidak boleh mengubah rencana/materi milik guru. Implementasi memberi admin `plans.manage`, scope seluruh plan, dan bypass owner; test bulk plan bahkan mengharapkan admin dapat menghapus RPP guru. Spec 08 juga mendokumentasikan bypass admin.

**Rekomendasi:** putuskan aturan bisnis kanonik. Bila admin memang supervisor global, perbaiki business rules. Bila tidak, cabut bypass dan ubah test.

### P1 — RULE-02: Scope guru untuk referensi Informatika mempunyai bypass khusus

Spec 06 menyatakan guru hanya mengelola mapel yang diampu. `ReferenceController::canManageSubject()` mengizinkan subject berkode `INF` tanpa memastikan assignment pada `subject_teachers` atau plan guru.

**Rekomendasi:** dokumentasikan exception workshop secara eksplisit atau hapus bypass; tambahkan test guru non-Informatika dan guru tanpa assignment.

### P1 — DOC-09: Verification tertinggal dari plan/tasks/implementation

Contoh terverifikasi:

- Spec 05 verification belum mencatat export/import dan bulk action Users.
- Spec 06 verification belum mencatat `ReferenceBulkActionTest`.
- Spec 08 verification belum mencatat `PlanBulkActionTest`.
- Spec 09 verification belum mencatat `MaterialExportTest` dan `MaterialBulkActionTest`.
- Spec 19 tasks menyatakan Vitest dan Playwright selesai, tetapi verification tidak memuat perintah/hasil keduanya.

**Rekomendasi:** jadikan verification manifest terstruktur yang divalidasi terhadap daftar artifact/test pada implementation dan tasks.

### P1 — GOV-01: Status `ADOPTED` tidak konsisten dengan checklist sumber

Pedoman discussions mensyaratkan implementasi, spec, dan handover selesai sebelum `ADOPTED`. Namun thread user import masih mempunyai checklist implementasi/verifikasi yang belum dicentang; thread bulk actions masih menyimpan langkah replikasi yang belum dicentang, sementara spec terkait menyatakan `done`.

**Rekomendasi:** tandai bagian sebagai baseline historis dan tambah blok outcome, atau selaraskan checklist sebelum menggunakan `ADOPTED`.

### P2 — DOC-05: Versi minimum PHP bertentangan

`docs/README.md` mensyaratkan PHP 8.4+, sedangkan `composer.json` mengizinkan `^8.3`. CI memakai 8.4.

**Rekomendasi:** bila fitur memang kompatibel dengan 8.3, dokumentasikan 8.3 sebagai minimum dan 8.4 sebagai runtime CI/produksi. Bila 8.4 wajib, ubah constraint Composer.

### P2 — DOC-06: Status plan bukan nilai status yang tegas

Banyak `docs/spec/*/plan.md` berisi `Status | selesai / aktif`, seolah menampilkan daftar opsi, bukan status aktual. `spec/README.md` menyatakan debt hanya berasal dari baris `todo`, tetapi bentuk task tidak seragam dan hasil verifikasi lama masih dicampur dengan state terkini.

**Rekomendasi:** gunakan satu enum per dokumen (`planned`, `active`, `verified`, `deprecated`) serta metadata `last_verified_at` dan `verified_commit`.

### P2 — DOC-07: Tautan absolut lokal tidak portabel

Banyak dokumen diskusi dan audit memakai `file:///c:/Users/jejak/...`. Tautan hanya bekerja pada mesin dan lokasi workspace ini, tidak di GitHub, CI artifact, clone lain, atau OS lain.

**Rekomendasi:** ubah menjadi path relatif repo; audit link secara otomatis di CI.

### P2 — DOC-08: Verifikasi spec belum mempunyai provenance

Checklist “hijau” dan angka test pada `verification.md` umumnya tidak menyimpan tanggal, commit SHA, versi runtime, database, atau link CI. Pernyataan historis terlihat seperti status berjalan.

**Rekomendasi:** pisahkan acceptance criteria dari verification run. Catat hasil run sebagai append-only evidence atau tautkan ke CI.

### P2 — DOC-10: Kontrak Word tidak konsisten

Discussion/task menggambarkan OOXML `.docx` melalui PhpWord, ADR menyebut `application/vnd.ms-word` dengan markup kompatibel Word, dan implementation menyebut “Word XML”. Ketiganya bukan kontrak format yang identik.

**Rekomendasi:** tetapkan extension, MIME, generator, dan compatibility target per endpoint; tambahkan test signature/content-type.

### P2 — DOC-11: Versi tooling dan diagram smoke stale

`docs/README.md` menyebut Vite 7 sementara dependency aktual Vite 8. Spec 19 menggambar route kuis `/quiz/{id}/attempt`, sedangkan route aktual GET/POST `/quiz/{quiz}`. Tahap 14 di indeks masih bernama “Export PDF” walaupun scope telah menjadi multi-format.

**Rekomendasi:** sinkronkan versi dari lock/package metadata dan validasi contoh route terhadap `route:list`.

### P2 — PERF-01: Indeks redundan yang ditemukan audit lama masih ada

Migration `create_activity_tables.php` membuat unique index `(plan_id, student_id)` dan index tunggal `plan_id` pada `attendance_records`. Pada MySQL/InnoDB, index tunggal tersebut umumnya redundan karena leftmost prefix.

Pola serupa ada pada `quiz_attempts`: unique `(quiz_id, student_id)` ditambah index `quiz_id`.

**Rekomendasi:** konfirmasi dengan `SHOW INDEX` dan workload nyata sebelum membuat migration penghapusan index.

### P2 — FE-01: Temuan frontend lama sebagian masih relevan

`Btn.vue` masih merender `<a href>` untuk seluruh prop `href`, sehingga tautan internal dapat hard reload bila caller tidak memakai Inertia `Link`. Temuan aksesibilitas audit lama masuk akal sebagai tema, tetapi laporannya tidak menyertakan inventaris elemen dan hasil alat a11y sehingga severity belum dapat dibuktikan.

**Rekomendasi:** bedakan internal navigation dari download/external link, dan tambahkan pemeriksaan axe/Playwright untuk label, keyboard, dialog focus, serta contrast.

## Validasi dua audit agen lain

### Audit standar rekayasa perangkat lunak komprehensif

Temuan yang **benar dan sudah diperbaiki pada working tree saat ini**:

- pelanggaran Pint: saat ini Pint lulus;
- N+1 `AttendanceSummaryController`: sudah memakai eager loading;
- N+1 `TeacherReportController`: relasi `evaluation` sudah di-eager-load;
- atomisitas `PlanController::bulkDestroy`: sudah memakai transaksi;
- cleanup TipTap: `editor.value?.destroy()` sudah ada;
- raw API key pada props, `$hidden`, dan rate limiter endpoint AI: sudah diperbaiki.

Temuan yang **masih berlaku atau perlu tindak lanjut**:

- index redundan;
- hard reload potensial pada `Btn.vue`;
- dominasi inline validation sebagai debt maintainability;
- audit aksesibilitas yang lebih terukur.

Klaim yang **keliru, stale, atau tidak terbukti**:

- skor 80–90/100 tidak mempunyai rubric dan evidence weighting;
- “PHPStan level tertinggi/maksimal” serta “murni” mengabaikan exclusion dan versi tool;
- metodologi “query profiling” dan “dynamic inspection” tidak disertai output/profil;
- bundle TipTap 452,44 kB (gzip 141,11 kB) tidak cukup untuk menyimpulkan “sangat ringan” tanpa budget/baseline;
- status temuan tidak diperbarui setelah remediasi, sehingga report mencampur snapshot lama dengan kondisi sekarang.

### Audit kesenjangan proyek dan dokumentasi

Hal yang **benar dan bernilai**:

- menemukan drift route, ekspor, bulk actions, spec, dan lokasi controller;
- perubahan working tree memang memindahkan export controller ke namespace domain;
- steering/spec telah diperluas untuk fitur export/import, attendance, dan bulk actions.

Hal yang **tidak dapat diterima sebagai kesimpulan audit**:

- persentase awal/pasca-harmonisasi tidak mempunyai metode hitung;
- tabel dimensi menyebut total jumlah temuan yang tidak konsisten dengan kesimpulan “11 poin”;
- klaim 100% parity dibantah oleh gap baseline PHPStan, angka QA, CI, skema `ai_providers`, route contract, dan link absolut;
- “status QA terkini” 193/1.287 sudah bukan baseline aktual;
- laporan tidak membedakan perubahan committed dari working tree, sehingga hasil tidak dapat direproduksi hanya dari branch/commit.

## Kekuatan yang terverifikasi

- Struktur dokumentasi konsisten dan mudah dinavigasi.
- Seluruh 19 tahap mempunyai empat jenis dokumen utama.
- ADR, discussions, dan spec membentuk alur keputusan yang cukup jelas.
- Backend test, PHPStan, Pint, Vitest, dan build saat ini lulus.
- Remediasi keamanan API key ke browser dan performa query utama sudah nyata di kode.
- Route dan dokumentasi fitur export/bulk action jauh lebih selaras dibanding kondisi sebelum audit agen lain.

## Urutan tindakan yang disarankan

### 24 jam

1. Perbaiki kontrak `BulkConfirmModal` pada Plans, Materials, dan References beserta test regresi.
2. Tutup bypass otorisasi re-parenting CP/TP/ATP dan formula injection exporter.
3. Nonaktifkan registrasi publik sesuai setting atau hapus klaim bahwa flag sudah aktif.
4. Koreksi klaim 100%, angka QA, baseline PHPStan, enkripsi API key, dan path media.
5. Nyatakan Playwright “belum tervalidasi pada baseline ini”.
6. Tambahkan gate frontend/Pint/build ke CI sebelum deploy atau revisi kontrak QA.

### 7 hari

1. Jadikan E2E reproducible dan aktifkan di CI.
2. Sinkronkan `database-schema.md` dan `api-contract.md`.
3. Hardening test provider AI terhadap SSRF dan secret leakage.
4. Standarkan metadata status/verifikasi semua spec.

### 30 hari

1. Tambahkan doc lint: broken link, absolute local link, duplicate/stale metrics, dan artifact existence.
2. Tambahkan traceability ID acceptance → route/controller/test.
3. Validasi index database dengan query plan/workload.
4. Jalankan audit aksesibilitas terukur dan performance budget frontend.

## Kesimpulan

Audit agen lain menghasilkan remediasi kode dan dokumentasi yang berguna, tetapi kualitas kesimpulannya terlalu optimistis. Status yang dapat dipertanggungjawabkan adalah: **struktur dokumentasi matang, banyak drift terbaru sudah ditutup, tetapi keselarasan dan QA enforcement belum penuh**. Prioritas terbesar bukan menambah skor baru, melainkan membuat klaim dapat direproduksi melalui CI, provenance, dan pemeriksaan kontrak otomatis.
