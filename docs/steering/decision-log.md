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
- **Alasan:** Hindari broken image; aset di storage sendiri.
- **Alternatif:** Auto DALL·E tanpa cek provider — ditolak sementara.
- **Dampak:** Co-Pilot kondisional di `Materials/Edit.vue`; `php artisan storage:link` wajib.

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
- **Dampak:** `resources/js/Pages/**`, `Http/Controllers/{Domain}`, Pest feature Inertia/HTTP; spek kemampuan di `docs/spec/01–14`.

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
