# Decision Log — Aksara

Catat keputusan arsitektur/produk yang mengubah arah kerja. Format mengikuti ADR ringan.

---

## ADR-001: Output AI selalu draf dan butuh publish manual guru

- **Tanggal:** 2026-08-09
- **Status:** diterima
- **Konteks:** Risiko pedagogis dan privasi jika AI langsung menerbitkan materi ke siswa.
- **Keputusan:** Hasil AI disimpan di `ai_generations` dengan `review_status=pending`. Materi hanya muncul ke siswa setelah guru approve lalu publish (`learning_plans` / `learning_materials` → `published`).
- **Alasan:** Guru tetap bertanggung jawab atas kesesuaian kurikulum; AI hanya asisten draf.
- **Alternatif:** Auto-publish setelah generate — ditolak.
- **Dampak:** Alur UI wajib langkah review (`Plans/Draft.vue` via `PlanController`); tidak ada jalur publish dari AI service.

---

## ADR-002: AI dipanggil hanya dari backend + mock mode workshop

- **Tanggal:** 2026-08-09
- **Status:** diterima
- **Konteks:** API key tidak boleh bocor ke browser; workshop sering offline.
- **Keputusan:** Integrasi lewat `App\Services\AiDraftService`. `AI_MOCK_MODE=true` mengembalikan draf deterministik tanpa HTTP. Produksi memakai katalog `ai_providers` + failover.
- **Alasan:** Keamanan key + kelancaran bimtek.
- **Alternatif:** Panggil AI dari frontend — ditolak.
- **Dampak:** Semua generasi AI lewat service; env `AI_*` didokumentasikan tanpa nilai rahasia; UI hanya memicu endpoint Inertia/JSON.

---

## ADR-003: Otorisasi utama lewat `users.role` + middleware `EnsureRole`

- **Tanggal:** 2026-08-09
- **Status:** diterima (dilengkapi ADR-007)
- **Konteks:** Spatie Permission terpasang; kebutuhan workshop butuh cek role sederhana.
- **Keputusan:** Kolom `users.role` (enum) menjadi sumber identitas dashboard/sidebar. Route fitur memakai permission Spatie (ADR-007). `EnsureRole` tetap untuk beberapa gate berbasis role.
- **Alasan:** Sederhana, eksplisit, mudah diuji.
- **Alternatif:** Hanya Spatie / hanya Policy — Policy masih opsional.
- **Dampak:** Agent tidak boleh menghapus middleware `role`/`permission` tanpa pengganti setara. Nav Inertia dibangun dari permission + role.

---

## ADR-004: ~~UI domain Livewire~~ → diganti ADR-010

- **Tanggal:** 2026-08-09
- **Status:** diganti (ADR-010)
- **Keputusan historis:** sempat memakai Livewire untuk form domain; diganti Inertia + Vue.
- **Dampak sekarang:** tidak berlaku. Stack UI = Inertia controllers + Vue pages.

---

## ADR-005: Satu attempt kuis per siswa

- **Tanggal:** 2026-08-09
- **Status:** diterima
- **Konteks:** Kuis harian sederhana untuk demo penilaian.
- **Keputusan:** Unique (`quiz_id`, `student_id`); skor otomatis 0–100.
- **Alasan:** Cukup untuk vertical slice; hindari kompleksitas retake.
- **Alternatif:** Multi-attempt — ditunda.
- **Dampak:** `Quiz/Attempt.vue` + `QuizAttemptController` menolak/menjelaskan bila attempt sudah ada.

---

## ADR-006: Role Administrator mengelola pengguna (bukan guru)

- **Tanggal:** 2026-08-09
- **Status:** diterima
- **Konteks:** Pengelolaan akun bersifat operasional sistem.
- **Keputusan:** `UserRole::Admin`. Hub `/users` ber-permission `users.manage`. Shell UI sama (`AppLayout` + sidebar), bukan panel admin terpisah.
- **Alasan:** Pemisahan tanggung jawab.
- **Alternatif:** Guru kelola user — ditolak. AdminLTE terpisah — ditolak.
- **Dampak:** Seed `admin@aksara.test`; dashboard `Pages/Dashboard/Admin.vue`.

---

## ADR-007: Permission matrix Spatie pada role tetap (bukan CRUD role)

- **Tanggal:** 2026-08-09
- **Status:** diterima
- **Konteks:** Hak akses lebih halus tanpa memecah enum `users.role`.
- **Keputusan:** Role tetap dari `UserRole`. Admin kelola matrix di `/access` (`Access/Index.vue`). Route fitur: `permission:*`. Identity dashboard: `users.role`. Permission wajib admin terkunci.
- **Alasan:** CRUD role bebas membuat enum/middleware/nav out of sync.
- **Alternatif:** CRUD role Spatie bebas — ditolak.
- **Dampak:** `PermissionCatalog`, seeder, shared permissions ke Inertia untuk nav/`useCan`.

---

## ADR-008: Ilustrasi materi — teks/link dulu; file hanya dari storage tepercaya

- **Tanggal:** 2026-08-10
- **Status:** diterima
- **Konteks:** Provider workshop sering teks-only; model chat sering menghallusinasi `<img>` broken.
- **Keputusan:**
  1. Ceklis **Generate Gambar AI** hanya jika provider image aktif + key.
  2. Ceklis **Link Gambar Ilustrasi**: deskripsi, prompt, tautan Unsplash/Wikimedia — bukan hotlink file.
  3. Sanitasi HTML (`MaterialContentHtml`): hanya `data:image/...` dan `/storage/...`.
  4. Upload ke disk `public` `materials/{material_id}/` dari TipTap Vue.
  5. **Extend:** media **context-scoped** — list/upload/delete hanya folder konteks aktif (fase 1: materi). Tidak ada library user/sekolah atau File Manager global. TipTap menerima prop `media` `{ listUrl, uploadUrl, deleteUrl }`.
- **Alasan:** Hindari broken image; aset di storage sendiri; isolasi antar materi/user.
- **Alternatif:** Auto DALL·E tanpa cek provider; DAM/library bersama — ditolak sementara.
- **Dampak:** Co-Pilot kondisional di `Materials/Edit.vue`; `php artisan storage:link` wajib; API `materials.media*`; spec **15/16**.

---

## ADR-011: TipTap global + STEM via `withMath`

- **Tanggal:** 2026-08-10
- **Status:** diterima
- **Konteks:** Editor sempat digabung spek materi; KaTeX hanya post-render Show.
- **Keputusan:**
  1. TipTap = komponen global (`Components/tiptap`) — spek **15**, terpisah dari materi/Co-Pilot (**09**).
  2. Prop `withMath` (bukan Blade `with-math`): toolbar rumus + lazy KaTeX; insert `$…$` / `$$…$$`.
  3. Media picker = spek **16** (context-scoped).
- **Alasan:** Reuse di Evaluation/dll.; zero overhead non-STEM.
- **Alternatif:** Editor khusus materi — ditolak.
- **Dampak:** `TipTapEditor` props; `MediaPicker.vue`; docs `15-tiptap-editor`, `16-context-media`.

---

## ADR-012: Design system Vue SoT + light enterprise coastal (permanen)

- **Tanggal:** 2026-08-10 (amandemen enterprise: 2026-08-11; coastal tipis: 2026-08-11; densitas UI & pola aksi: 2026-08-11)
- **Status:** diterima
- **Konteks:** Cutover Inertia+Vue; visual rules tersebar; aksen ungu/ad-hoc; kebutuhan workshop tanpa dark mode; ingin kesan admin/enterprise tanpa menghapus brand; tren 2026 quiet utility vs glassmorph; list panjang butuh pagination; aksi form kiri tidak konsisten.
- **Keputusan:**
  1. Spec **17-design-system** = SoT visual (token, tipografi, ikon, kontrak `Components/ui`).
  2. Spec **03** hanya app shell / nav.
  3. **Light-only permanen** — dark mode **tidak digarap** (bukan backlog).
  4. Arah visual **enterprise-education + coastal tipis**: brand teal + netral sea-mist; surface border-first; tipografi UI sans-first.
  5. **Bukan glassmorphism** (hindari blur/frosted penuh); depth = border + wash matte.
  6. Larangan palette ad-hoc (ungu, cream/terracotta generik, dll.) dan library ikon kedua tanpa ADR.
  7. List panjang: `Pagination` + `per_page` 10/25/50/100.
  8. Aksi form/modal rata kanan; toolbar tab/section pakai `.aksara-toolbar`.
  9. Ekspor multi-format: `ExportMenu` (satu tombol → popup).
- **Alasan:** Coastal melunakkan slate tanpa menghapus identitas teal; glass penuh bentrok admin console & aksesibilitas; quiet utility 2026 lebih tahan lama untuk bimtek; densitas compact + aksi kanan mengurangi scroll dan inkonsistensi.
- **Alternatif:** Dark mode; navy SaaS penuh; glassmorph penuh; shadcn/Storybook — ditolak.
- **Dampak:** Token di `app.css` / `tailwind.config.js`; shell/landing/guest wash; coding-standards → 17; komponen `Pagination`/`IconButton`/`ExportMenu`.

---

## ADR-009: Co-Pilot tunggal untuk materi + intent create/patch/rewrite

- **Tanggal:** 2026-08-10
- **Status:** diterima
- **Konteks:** Dual CTA generate membingungkan; apply selalu full override.
- **Keputusan:** Satu Co-Pilot (sidebar). Intent: `create` / `patch` / `rewrite`. Patch merge di PHP. Model rekomendasi per fitur di settings.
- **Alasan:** Hindari hilangnya konten guru.
- **Alternatif:** Dual CTA — ditolak.
- **Dampak:** `MaterialEditController@copilot` + `AiDraftService::chatRefineMaterial`; UI `Materials/Edit.vue`.

---

## ADR-010: UI = Laravel + Inertia + Vue

- **Tanggal:** 2026-08-10
- **Status:** diterima (cutover + cleanup selesai)
- **Konteks:** TipTap NodeView membutuhkan integrasi first-class di Vue; domain PHP sudah matang.
- **Keputusan:**
  1. Stack UI: **Inertia.js + Vue 3 + TipTap Vue** + Tailwind.
  2. Domain PHP dipertahankan (Models, Services, Enums, Support, middleware, migrations).
  3. Blade hanya root Inertia (`app.blade.php`) + `exports/` PDF.
  4. Tetap **bukan** REST API publik (session web).
- **Alasan:** TipTap first-class di Vue; DX jelas untuk app session-based di Laravel.
- **Alternatif:** Blade + island TipTap; SPA terpisah + API — di luar scope bimtek.
- **Dampak:** `resources/js/Pages/**`, `Http/Controllers/{Domain}`, Pest feature Inertia/HTTP; spek kemampuan di `docs/spec/01–17`.

---

## ADR-013: MIT License + short source file headers

- **Tanggal:** 2026-08-11
- **Status:** diterima
- **Konteks:** Perlu identitas produk/pengembang yang jelas lintas agen (Cursor dan non-Cursor); kebijakan clone/fork/modifikasi; menghindari header lisensi penuh di setiap file.
- **Keputusan:**
  1. Root `LICENSE` = **MIT**, copyright **jejakawan** (https://jejakawan.com); produk **Aksara**.
  2. `NOTICE` merangkum kebijakan clone/fork/modifikasi + pointer ke header.
  3. Source app memakai **header ringkas** (`@copyright` / `@license MIT`) — template SoT: `docs/steering/file-header.md`.
  4. Arahan agen-agnostik di `handover.md`; Cursor: `.cursor/rules/file-header.mdc`.
  5. Scope header: `app/`, `bootstrap/*.php`, `config/`, `database/{migrations,seeders,factories}/`, `routes/`, `resources/{js,css,views}/`, `tests/`. Kecualikan vendor/build/storage.
- **Alasan:** MIT selaras izin clone/fork/modifikasi; header pendek mengurangi noise diff; handover memastikan tool selain Cursor tetap patuh.
- **Alternatif:** Proprietary “all rights reserved”; header teks LICENSE penuh di tiap file — ditolak.
- **Dampak:** `LICENSE`, `NOTICE`, `file-header.md`, rule Cursor, mass-insert header pada source existing; UI atribusi via `BrandCopyright` + `BrandAttribution` (soft boot check).

---

## ADR-014: Standarisasi Ekspor Dokumen Multi-Format Sekolah (PDF Blade Berkop, Excel PhpSpreadsheet, DOCX & Markdown)

- **Tanggal:** 2026-09-16
- **Status:** diterima
- **Konteks:** Kebutuhan cetak fisik dan pengarsipan digital sekolah (RPP, Materi Pembelajaran, Rekap Kehadiran, Kurikulum CP/TP/ATP). Kebutuhan cetak resmi menuntut Kop Surat sekolah dinamis dan tanda tangan; kebutuhan data analitis menuntut Excel (.xlsx); kebutuhan offline menuntut Word (.docx) dan Markdown (.md).
- **Keputusan:**
  1. Standar PDF menggunakan Blade view cetak A4 (Portrait untuk RPP/Materi/Kurikulum, Landscape untuk Rekap Kehadiran) dengan partial kop surat resmi (`resources/views/exports/partials/kop.blade.php`), konfigurasi `@page`, styling CSS print-optimized, dan blok tanda tangan guru/kepala sekolah.
  2. Standar Spreadsheet menggunakan `PhpOffice\PhpSpreadsheet` murni untuk format `.xlsx` dengan styling header Aksara Teal (`#0D9488`), auto-column sizing, auto-filter, dan format persentase.
  3. Standar Word menggunakan MIME `application/vnd.ms-word` dengan markup dokumen yang kompatibel dengan Microsoft Word; Markdown murni (`.md`) untuk transfer konten portabel.
  4. Seluruh antarmuka ekspor menggunakan komponen UI tunggal `ExportMenu.vue` (popup menu dengan opsi format) yang membaca filter aktif via URL query parameters.
- **Alasan:** Menghilangkan dependensi biner headless browser yang berat di server lokal/cloud, menghasilkan dokumen cetak yang sesuai format tata naskah dinas sekolah Indonesia, dan menjaga konsistensi UX.
- **Alternatif:** Headless Chrome/Puppeteer PDF — ditolak karena kebutuhan resource berat; CSV generik — ditolak karena kurang rapi untuk administrasi sekolah.
- **Dampak:** Controller `MaterialExportController`, `AttendanceExportController`, `LearningPlanExportController`, `CurriculumExportController`; service domain terkait; Blade templates di `resources/views/exports/*`.

---

## ADR-015: Manajemen Pengguna Massal, Pipa Impor Excel Terpandu, & Tata Kelola Kredensial Acak

- **Tanggal:** 2026-09-16
- **Status:** diterima
- **Konteks:** Onboarding ratusan siswa dan guru di awal tahun ajaran baru memakan waktu jika dilakukan satu per satu. Diperlukan fitur impor massal via Excel dengan template standar, opsi penanganan duplikasi, dan distribusi kredensial yang aman.
- **Keputusan:**
  1. Format impor/ekspor menggunakan Excel `.xlsx` dengan validasi struktur header ketat dan template resmi (`/users/template`).
  2. Kebijakan duplikasi: Admin dapat memilih `skip` (abaikan baris yang emailnya sudah terdaftar) atau `update` (perbarui nama dan role).
  3. Password akun baru dapat dipilih seragam (mis. password default sekolah) atau acak kriptografis (8 karakter unik per akun).
  4. Kredensial acak sementara disimpan dalam session flash dan dapat diunduh langsung (`/users/credentials-download`) sebelum sesi berakhir demi privasi data dan kepatuhan audit.
  5. Aksi massal penghapusan (`bulkDestroy`) wajib mematuhi guardrails: tidak boleh menghapus diri sendiri (`Auth::id()`), dan tidak boleh menghapus akun guru dengan RPP aktif serta wali kelas dengan rombel aktif.
- **Alasan:** Efisiensi operasional admin sekolah tanpa mengorbankan integritas data relasional dan keamanan kredensial siswa/guru.
- **Alternatif:** Import CSV tanpa validasi duplikasi — ditolak; kirim password via email publik — ditolak untuk workshop offline.
- **Dampak:** Controller `UserExportImportController`, service `UserExportImportService`, modal `UserImportModal.vue`, composable `useBulkSelect.js`, toolbar `BulkToolbar.vue`, dan modal konfirmasi `BulkConfirmModal.vue`.

---

## ADR-016: Protokol Penegakan QA Berlapis, PHPStan Level 9, & Smoke Test Multi-Role

- **Tanggal:** 2026-09-16
- **Status:** diterima
- **Konteks:** Kompleksitas aplikasi dengan 5 peran pengguna dan integrasi AI menuntut jaminan kualitas tinggi agar regresi tidak lolos ke tahap rilis.
- **Keputusan:**
  1. Static Analysis wajib lulus **PHPStan / Larastan Level 9** dengan 0 error dan 0 baseline additions baru.
  2. Pengujian otomatis backend wajib 100% lulus via Pest (`php artisan test`).
  3. Pengujian alur kritis wajib melalui `CriticalJourneySmokeTest.php` yang mensimulasikan satu alur bersambung lintas 5 peran (Admin ➔ Guru ➔ Siswa ➔ Guru ➔ Wali Kelas ➔ Wali Murid).
  4. Pengujian frontend unit dijalankan via Vitest (`npm run test:unit`) dan E2E browser via Playwright (`npm run test:e2e`).
  5. Build aset frontend Vite wajib bersih tanpa error sintaks/warning (`npm run build`).
- **Alasan:** Memberikan perlindungan komprehensif terhadap regresi logika bisnis, celah otorisasi antar role, dan kompatibilitas tipe data.
- **Alternatif:** QA manual saja — ditolak; PHPStan Level 5 — ditingkatkan ke Level 9.
- **Dampak:** `tests/Feature/Smoke/CriticalJourneySmokeTest.php`, `phpstan.neon`, `phpstan-baseline.neon`, workflow CI GitHub Actions, dan aturan coding standards.

---

## Template entri baru

```markdown
## ADR-00X: [Judul keputusan]

- **Tanggal:**
- **Status:** diusulkan / diterima / diganti
- **Konteks:**
- **Keputusan:**
- **Alasan:**
- **Alternatif yang dipertimbangkan:**
- **Dampak:**
```
