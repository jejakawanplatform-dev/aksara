# Spec — dokumentasi kemampuan (stack terkini)

Setiap tahap = empat dokumen. **Sumber lokasi artefak** ada di `implementation.md` dan bagian **Lokasi artefak** pada `verification.md` — path Inertia/Vue/Laravel yang dipakai sekarang.

| File | Isi |
|---|---|
| `plan.md` | Status, ringkasan, tujuan, scope, acceptance |
| `tasks.md` | Checklist (done / todo debt) |
| `implementation.md` | Tabel path Controller / Pages / Services + alur |
| `verification.md` | Lokasi artefak + perintah test + uji manual |

**Stack:** Laravel 13 + Inertia.js + Vue 3 + TipTap Vue.  
Blade hanya `resources/views/app.blade.php` + `resources/views/exports/*`.

## Tahap

| Kode | Kemampuan | Folder |
|---|---|---|
| 01 | Scaffold & boot Inertia | [01-scaffold-inertia](01-scaffold-inertia/) |
| 02 | Auth & profil | [02-auth-profile](02-auth-profile/) |
| 03 | App shell & navigasi | [03-app-shell-ui](03-app-shell-ui/) |
| 04 | Role & RBAC matrix | [04-rbac-access](04-rbac-access/) |
| 05 | Manajemen pengguna | [05-users](05-users/) |
| 06 | Referensi kurikulum & sekolah | [06-references](06-references/) |
| 07 | Settings & AI providers | [07-settings-ai](07-settings-ai/) |
| 08 | Rencana pembelajaran & draf AI | [08-learning-plans](08-learning-plans/) |
| 09 | Materi & Co-Pilot | [09-materials-copilot](09-materials-copilot/) |
| 10 | Kuis | [10-quizzes](10-quizzes/) |
| 11 | Kehadiran | [11-attendance](11-attendance/) |
| 12 | Evaluasi & monitoring | [12-evaluation](12-evaluation/) |
| 13 | Dashboard & laporan | [13-dashboards-reports](13-dashboards-reports/) |
| 14 | Export PDF (Blade) | [14-exports-pdf](14-exports-pdf/) |
| 15 | TipTap rich editor (global) | [15-tiptap-editor](15-tiptap-editor/) |
| 16 | Context-scoped media | [16-context-media](16-context-media/) |
| 17 | Design system (Vue SoT) | [17-design-system](17-design-system/) |

## Untuk agent

1. Pakai tabel **Lokasi artefak** di `verification.md` / `implementation.md` sebagai path kanonik.
2. Debt hanya dari baris `todo` di `tasks.md`.
3. Jangan menambah stack UI di luar Inertia + Vue tanpa ADR.

Template: [`_template/`](_template/).
