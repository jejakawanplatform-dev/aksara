# Coding Standards — Aksara

Standar ini wajib dipatuhi manusia maupun AI agent.

## 1. Prinsip

1. **Benar sebelum cepat** — ikuti `business-rules.md`.
2. **Aman sebelum nyaman** — otorisasi backend tidak boleh dilewati demi demo.
3. **Kecil dan teruji** — ubah modul sempit; sertakan bukti test.
4. **Terbaca dan konsisten** — kode harus bisa dilanjutkan orang/agent lain.
5. **Terdokumentasi** — keputusan penting masuk `decision-log.md` / `handover.md`.
6. **Header & lisensi** — file source baru wajib header singkat per `file-header.md` (MIT / jejakawan).

## 2. Stack & tool

| Aspek | Standar Aksara |
|---|---|
| PHP | 8.4; pakai enum, named args, match bila cocok |
| Framework | Laravel 13 |
| UI | **Inertia.js + Vue 3** + Tailwind (ADR-010) |
| Editor konten | TipTap via `@tiptap/vue-3` (`Components/tiptap`) |
| Auth pages | Breeze Inertia → `Pages/Auth/*` |
| PDF / export view | Blade di `resources/views/exports` saja |
| Format | `vendor/bin/pint` |
| Static analysis | Larastan / PHPStan **Level 9** (`vendor/bin/phpstan analyse --memory-limit=1G`) |
| Test | `php artisan test` (Pest); assert Inertia / HTTP feature + Smoke Test |
| Assets | `npm run build` / `npm run dev` (Vite entry: `inertia-app.js`) |
| Config | Hanya lewat `.env` / `config/*` — tanpa hard-code secret |

Stack UI di luar Inertia + Vue memerlukan ADR baru.

## 3. Penamaan

| Jenis | Gaya |
|---|---|
| Class / Model / Controller | `PascalCase` |
| Vue SFC / pages | `PascalCase.vue` (`Materials/Edit.vue`) |
| Method / variabel | `camelCase` |
| Tabel / kolom / migration | `snake_case` |
| Enum case | `PascalCase` (value string `snake_case` bila perlu) |
| Route name | `dot.notation` (`plans.create`) |
| Permission | `dot.notation` (`materials.read`) |

## 4. Struktur yang diutamakan

```text
app/
  Enums/
  Http/Controllers/{Domain}/   ← adapter UI Inertia
  Http/Middleware/
  Models/
  Services/                    ← domain & AI (sumber kebenaran)
  Support/
database/migrations|seeders/
resources/js/
  inertia-app.js               ← createInertiaApp
  bootstrap.js
  Layouts/                     ← AppLayout, GuestLayout, Sidebar, Topbar
  Components/ui/               ← Btn, Card, Field, Flash, StatusBadge, PageHeader,
                               ← Alert, EmptyState, Table, Loading, Icon, Modal,
                               ← Pagination, IconButton, ExportMenu, PasswordInput
  Components/tiptap/           ← TipTapEditor.vue, TipTapToolbar.vue, aksara-image.js, MediaPicker.vue
  Pages/{Domain}/              ← satu page per layar
  Composables/                 ← authValidation.js, …
  lib/
resources/views/
  app.blade.php                ← root Inertia (satu-satunya shell HTML app)
  exports/                     ← PDF Blade
routes/web.php|auth.php
docs/steering|spec/
tests/Feature|Unit|Smoke/
```

### Aturan arsitektur

1. Logika AI & integrasi eksternal di **Service**, bukan di Vue/Blade.
2. Validasi di **Form Request** atau `$request->validate()` pada Controller Inertia.
3. Authorization: middleware `permission` / `role` + cek kepemilikan di controller.
4. Jangan menaruh API key di frontend / repository.
5. Perubahan skema hanya lewat migration.
6. UI fitur baru = **page Vue** + **Controller** di `Http/Controllers/{Domain}`; reuse `Components/ui`.
7. Mutasi biasa via Inertia form (`useForm` / `router`); JSON/XHR hanya untuk partial update (upload gambar, Co-Pilot chat).
8. Jangan menambah Blade layout/komponen app baru — kecuali export PDF.

## 5. Inertia + Vue

- Resolve pages: `resources/js/Pages/**/*.vue` via `import.meta.glob`.
- Alias import: `@/` → `resources/js/`.
- Layout authenticated: `Layouts/AppLayout.vue` + shared `auth` / `nav` / flash dari `HandleInertiaRequests`.
- Layout guest: `Layouts/GuestLayout.vue`.
- TipTap: `@tiptap/vue-3` + `Components/tiptap/*`; props `withMath` + `media` (list/upload/delete URL). KaTeX lazy hanya bila `withMath`.
- Design system SoT: `docs/spec/17-design-system` + token `.aksara-*` / `aksara.*` Tailwind (ADR-012 light enterprise + coastal tipis; bukan glassmorph).
- Design tokens / utility: class `.aksara-*` di CSS + komponen `Components/ui/*` (Btn/Modal/Pagination/IconButton/ExportMenu/…).
- List panjang: paginate di controller + `Pagination.vue`; form/modal aksi kanan (`.aksara-form-actions`); toolbar tab `.aksara-toolbar`.
- Tailwind `content` wajib include `resources/js/**/*.{js,vue}`.
- Config: `inertia.use_script_element_for_initial_page=true` (client Inertia v3).

## 6. Data & keamanan

1. Jangan kirim data pribadi siswa ke AI.
2. Validasi & authorize di server.
3. Soft delete untuk entitas pembelajaran yang sudah ada polanya.
4. Unique constraint untuk attempt/absensi/evaluasi dihormati di kode.
5. Jangan commit `.env`.
6. HTML materi disanitasi (`MaterialContentHtml`) sebelum persist/tampil.

## 7. Quality Assurance (QA) Wajib Sebelum Selesai

Setiap perubahan kode (backend maupun frontend) **WAJIB** melalui gerbang pengujian (QA gate) berikut sebelum pekerjaan dinyatakan selesai atau di-commit:

1. **Pest Test Suite (100% Pass):**
   ```bash
   php artisan test
   ```
   Seluruh pengujian unit dan fitur (146+ tests) wajib berstatus hijau (`passed`).
2. **Smoke Test Lintas Role (Critical Journey):**
   ```bash
   php artisan test --filter=CriticalJourneySmokeTest
   ```
   Wajib dijalankan terutama bila menyentuh routing, middleware, otorisasi, atau model inti 5 role (Admin, Guru, Siswa, Wali Kelas, Wali Murid).
3. **Frontend Unit Tests (Vitest):**
   ```bash
   npm run test:unit
   ```
   Pengujian unit logic JavaScript/composables (`useBulkSelect`, `authValidation`, dll.).
4. **Browser E2E Smoke Tests (Playwright):**
   ```bash
   npm run test:e2e
   ```
   Pengujian interaksi peramban nyata (TipTap editor, MediaPicker, render KaTeX, dan Bulk Actions dialog).
5. **Static Analysis PHPStan Level 9 (0 Error):**
   ```bash
   vendor/bin/phpstan analyse --memory-limit=1G
   ```
   Wajib menghasilkan output `[OK] No errors` pada **Level 9**. Larang menurunkan level analisis tanpa konsensus tim arsitek.
6. **Frontend Asset Build (0 Error & 0 Warning):**
   ```bash
   npm run build
   ```
   Wajib selesai tanpa kompilasi gagal, sintaks error, atau dependensi Vue/Tailwind yang hilang.
7. **Code Style Formatting:**
   ```bash
   vendor/bin/pint --test
   ```
   Format kode PHP harus mematuhi standar Laravel Pint.

## 8. Definition of Done (per tugas)

Sebuah tugas dianggap selesai bila:

- [ ] Sesuai scope & business rules
- [ ] Otorisasi permission/role/kepemilikan diverifikasi
- [ ] AI (bila ada) tetap menghasilkan draf + tervalidasi
- [ ] Page Vue memakai layout/komponen UI yang ada (bukan markup ad-hoc berlebihan)
- [ ] QA Gate lulus penuh (Pest 100%, Vitest pass, Playwright pass, PHPStan Level 9 = 0 error, npm run build = hijau)
- [ ] `handover.md` / `decision-log.md` diperbarui bila perlu
- [ ] Dokumentasi spesifikasi di `docs/spec/` atau diskusi di `docs/discussions/` diperbarui

## 9. Instruksi wajib ke agent

Setiap agen AI (manusia maupun otomatis) yang membuat atau memperbarui kode pada proyek Aksara **DILARANG KERAS** menyatakan tugas selesai atau melakukan serah-terima (`handover`) sebelum menjalankan verifikasi QA suite di atas.

Setiap prompt atau eksekusi agent harus memuat:

1. dokumen `/docs` yang wajib dibaca;
2. satu tujuan spesifik;
3. batas file/modul;
4. acceptance criteria;
5. batasan data/keamanan;
6. rencana sebelum implementasi;
7. **eksekusi QA suite wajib** (Pest + PHPStan L9 + Build) dan pencatatan buktinya di ringkasan serah-terima.

Template lengkap: lihat `docs/steering/handover.md` dan spek kemampuan di `docs/spec/` (piramida terbalik).

Header file & lisensi: `docs/steering/file-header.md` (wajib untuk semua agen).
