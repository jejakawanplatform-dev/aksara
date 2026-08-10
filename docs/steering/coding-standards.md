# Coding Standards — Aksara

Standar ini wajib dipatuhi manusia maupun AI agent.

## 1. Prinsip

1. **Benar sebelum cepat** — ikuti `business-rules.md`.
2. **Aman sebelum nyaman** — otorisasi backend tidak boleh dilewati demi demo.
3. **Kecil dan teruji** — ubah modul sempit; sertakan bukti test.
4. **Terbaca dan konsisten** — kode harus bisa dilanjutkan orang/agent lain.
5. **Terdokumentasi** — keputusan penting masuk `decision-log.md` / `handover.md`.

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
| Static analysis | Larastan / PHPStan level 5 (`vendor/bin/phpstan analyse`) |
| Test | `php artisan test` (Pest); assert Inertia / HTTP feature |
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
  Components/ui/               ← Btn, Card, Field, Flash, StatusBadge,
                               ← Alert, EmptyState, Table, Loading, Icon
  Components/tiptap/           ← TipTapEditor.vue, aksara-image.js
  Pages/{Domain}/              ← satu page per layar
  Composables/
  lib/
resources/views/
  app.blade.php                ← root Inertia (satu-satunya shell HTML app)
  exports/                     ← PDF Blade
routes/web.php|auth.php
docs/steering|spec/
tests/Feature|Unit/
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
- TipTap: `@tiptap/vue-3` + `Components/tiptap/*`; KaTeX hanya di-load bila mode STEM (`with-math`).
- Design tokens / utility: class `.aksara-*` di CSS + komponen `Components/ui/*`.
- Tailwind `content` wajib include `resources/js/**/*.{js,vue}`.
- Config: `inertia.use_script_element_for_initial_page=true` (client Inertia v3).

## 6. Data & keamanan

1. Jangan kirim data pribadi siswa ke AI.
2. Validasi & authorize di server.
3. Soft delete untuk entitas pembelajaran yang sudah ada polanya.
4. Unique constraint untuk attempt/absensi/evaluasi dihormati di kode.
5. Jangan commit `.env`.
6. HTML materi disanitasi (`MaterialContentHtml`) sebelum persist/tampil.

## 7. Definition of Done (per tugas)

Sebuah tugas dianggap selesai bila:

- [ ] Sesuai scope & business rules
- [ ] Otorisasi permission/role/kepemilikan diverifikasi
- [ ] AI (bila ada) tetap menghasilkan draf + tervalidasi
- [ ] Page Vue memakai layout/komponen UI yang ada (bukan markup ad-hoc berlebihan)
- [ ] Test relevan dijalankan / ditambahkan
- [ ] Pint/PHPStan tidak memperkenalkan regresi jelas
- [ ] `npm run build` hijau jika menyentuh frontend
- [ ] `handover.md` / `decision-log.md` diperbarui bila perlu

## 8. Instruksi wajib ke agent

Setiap prompt ke agent harus memuat:

1. dokumen `/docs` yang wajib dibaca;
2. satu tujuan spesifik;
3. batas file/modul;
4. acceptance criteria;
5. batasan data/keamanan;
6. rencana sebelum implementasi;
7. ringkasan test + risiko setelahnya.

Template lengkap: lihat `docs/steering/handover.md` dan spek kemampuan di `docs/spec/` (piramida terbalik).
