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
