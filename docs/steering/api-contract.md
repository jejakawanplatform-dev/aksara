# API / Route Contract — Aksara

Aksara adalah aplikasi **web session** dengan **Inertia.js + Vue 3** (ADR-010). Tidak ada `routes/api.php` domain. Kontrak di bawah adalah permukaan yang boleh diandalkan agent/developer.

Sumber route: `routes/web.php`, `routes/auth.php`.  
Sumber UI: `resources/js/Pages/**`.  
Shared props: `App\Http\Middleware\HandleInertiaRequests` (`auth`, `nav`, flash).

## Autentikasi

- Laravel Breeze (session + CSRF) — pages di `resources/js/Pages/Auth/*`.
- Middleware: `auth`, `verified`, `permission:{name}`, `role:{role}` (identitas dashboard / legacy `EnsureRole`).
- Permission: Spatie `PermissionMiddleware` (alias `permission`).
- Role: `App\Http\Middleware\EnsureRole` (alias `role`) — berbasis `users.role`.

## Route aplikasi

### Umum & auth

| Method | Path | Nama | Catatan | UI |
|---|---|---|---|---|
| GET | `/` | — | publik | `Welcome.vue` |
| GET | `/dashboard` | `dashboard` | auth+verified | `Dashboard/{Admin,Guru,Siswa,WaliKelas,WaliMurid,Generic}.vue` |
| GET/PATCH/DELETE | `/profile` | `profile.*` | auth | `Profile/Edit.vue` |
| GET/POST | `/login`, `/register`, … | `login`, … | Breeze | `Auth/*` |

### Admin — pengguna, akses, settings

| Method | Path | Nama | Permission | Controller → page |
|---|---|---|---|---|
| GET | `/users` | `users.index` | `users.manage` | `Users\UserController` → `Users/Index` |
| POST | `/users` | `users.store` | `users.manage` | Simpan pengguna baru |
| PUT | `/users/{user}` | `users.update` | `users.manage` | Update data / role pengguna |
| DELETE | `/users/{user}` | `users.destroy` | `users.manage` | Hapus pengguna individual |
| POST | `/users/bulk-destroy` | `users.bulk-destroy` | `users.manage` | Hapus pengguna massal (guard self & relasi aktif) |
| GET | `/users/export` | `users.export` | `users.manage` | Ekspor data pengguna Excel (.xlsx) |
| GET | `/users/template` | `users.template` | `users.manage` | Unduh template resmi Excel (.xlsx) |
| POST | `/users/import` | `users.import` | `users.manage` | Impor data pengguna Excel massal |
| GET | `/users/credentials-download` | `users.credentials-download` | `users.manage` | Unduh rekap password acak hasil impor |
| POST | `/users/{user}/attach-class` | `users.attach-class` | `users.manage` | Pasangkan siswa ke kelas rombel |
| DELETE | `/users/{user}/classes/{class}` | `users.detach-class` | `users.manage` | Lepaskan siswa dari kelas rombel |
| POST | `/users/{user}/attach-child` | `users.attach-child` | `users.manage` | Hubungkan wali murid dengan anak siswa |
| DELETE | `/users/{user}/children/{child}` | `users.detach-child` | `users.manage` | Putuskan relasi wali murid dengan anak siswa |
| POST | `/users/{user}/homeroom` | `users.homeroom` | `users.manage` | Tetapkan / simpan penugasan wali kelas |
| GET | `/access` | `access.index` | `access.manage` | `Access\AccessController` → `Access/Index` |
| PUT | `/access` | `access.save` | `access.manage` | Simpan matrix permission |
| POST | `/access/reset-defaults` | `access.reset-defaults` | `access.manage` | Reset matrix ke default katalog |
| GET | `/settings` | `settings.index` | `settings.manage` | `Settings\SettingsController` → `Settings/Index` |
| PUT | `/settings` | `settings.save` | `settings.manage` | Simpan preferensi model & AI |
| POST | `/settings/providers` | `settings.providers.store` | `settings.manage` | Tambah AI provider kustom |
| POST | `/settings/providers/test` | `settings.providers.test` | `settings.manage` | Test koneksi AI provider (`throttle:15,1`, SSRF protected) |
| PUT | `/settings/providers/{provider}` | `settings.providers.update` | `settings.manage` | Update AI provider |
| DELETE | `/settings/providers/{provider}` | `settings.providers.destroy` | `settings.manage` | Hapus AI provider kustom |
| POST | `/settings/providers/{provider}/toggle` | `settings.providers.toggle` | `settings.manage` | Aktifkan / nonaktifkan provider |
| POST | `/settings/providers/{provider}/priority` | `settings.providers.priority` | `settings.manage` | Naikkan / turunkan urutan prioritas failover |

### Guru — rencana pembelajaran

| Method | Path | Nama | Permission | Controller → page |
|---|---|---|---|---|
| GET | `/plans` | `plans.index` | `plans.manage` | `Plans\PlanController@index` → `Plans/Index` |
| GET | `/plans/create` | `plans.create` | `plans.manage` | → `Plans/Create` |
| POST | `/plans` | `plans.store` | `plans.manage` | Simpan rencana baru (manual/AI) |
| POST | `/plans/bulk-destroy` | `plans.bulk-destroy` | `plans.manage` | Hapus rencana pembelajaran massal |
| GET | `/plans/{plan}/edit` | `plans.edit` | `plans.manage` | → `Plans/Edit` |
| PUT | `/plans/{plan}` | `plans.update` | `plans.manage` | Update rencana |
| DELETE | `/plans/{plan}` | `plans.destroy` | `plans.manage` | Hapus rencana |
| POST | `/plans/{plan}/open-material` | `plans.open-material` | `plans.manage` | Buka atau inisialisasi draf materi dari RPP |
| GET | `/plans/{plan}/draft` | `plans.draft` | `plans.manage` | → `Plans/Draft` |
| POST | `/plans/{plan}/draft/approve` | `plans.draft.approve` | `plans.manage` | Approve draf AI |
| POST | `/plans/{plan}/draft/publish` | `plans.draft.publish` | `plans.manage` | Publish plan + materi |
| GET/POST | `/plans/{plan}/quiz` | `plans.quiz*` | `plans.manage` | → `Quiz/Form` |
| GET | `/plans/export/{format}` | `plans.export` | `plans.manage` | Ekspor kumpulan RPP (Excel/Word/PDF) |
| GET | `/plans/{plan}/export/{format}` | `plans.export.single` | `plans.manage` | Ekspor single RPP (Excel/Word/PDF) |
| POST | `/plans/import` | `plans.import` | `plans.manage` | Impor RPP |
| GET | `/plans/import/template` | `plans.import.template` | `plans.manage` | Unduh template impor RPP |

### Materi & Co-Pilot

| Method | Path | Nama | Permission | Catatan |
|---|---|---|---|---|
| GET | `/materials` | `materials.index` | `materials.read` \| `plans.manage` | `Materials/Index` |
| POST | `/materials/bulk-destroy` | `materials.bulk-destroy` | `materials.read` \| `plans.manage` | Hapus materi terpilih massal |
| GET | `/materials/{material}` | `materials.show` | sama | `Materials/Show` (+ learning event) |
| GET | `/materials/{material}/export/{format}` | `materials.export.single` | sama | Ekspor materi (PDF/Word/Markdown) |
| GET | `/materials/{material}/edit` | `materials.edit` | sama | `Materials/Edit` + TipTap Vue |
| PUT | `/materials/{material}` | `materials.update` | sama | Simpan konten JSON seksi |
| POST | `/materials/{material}/publish` | `materials.publish` | sama | Publish materi |
| GET | `/materials/{material}/media` | `materials.media` | sama | List file di disk `public`: `storage/app/public/materials/{id}/` |
| POST | `/materials/{material}/images` | `materials.images` | sama | Upload media → disk `public` (URL: `/storage/materials/...`) |
| DELETE | `/materials/{material}/media/{filename}` | `materials.media.destroy` | sama | Hapus file di disk `public` folder materi |
| POST | `/materials/{material}/copilot` | `materials.copilot` | sama | JSON Co-Pilot (`chatRefineMaterial`, throttle `15,1`) |

### Kehadiran, evaluasi, laporan, kuis siswa

| Method | Path | Nama | Permission | Page |
|---|---|---|---|---|
| GET/POST | `/plans/{plan}/attendance` | `attendance.*` | `attendance.manage` | `Attendance/Form` |
| GET | `/attendance/summary` | `attendance.summary` | `attendance.summary` | `Attendance/Summary` |
| GET | `/attendance/export/{format}` | `attendance.export` | `attendance.summary` | Ekspor rekap (PDF/Excel) |
| GET/POST | `/plans/{plan}/evaluation` | `evaluation.*` | `evaluation.manage` | `Evaluation/Form` |
| GET | `/evaluations/monitoring` | `evaluations.monitoring` | `evaluation.manage` | `Evaluation/Monitoring` |
| GET | `/reports/guru` | `reports.guru` | `reports.teacher` | `Reports/Teacher` |
| GET/POST | `/quiz/{quiz}` | `quiz.attempt*` | `quiz.attempt` | `Quiz/Attempt` |

### Referensi kurikulum & operasional

| Method | Path | Nama | Permission | Page |
|---|---|---|---|---|
| GET | `/references` | `references.index` | `references.view` | `References/Index` |
| GET | `/references/profil-sekolah` | `references.section.school` | `references.view` | Tab Profil Sekolah |
| GET | `/references/data-akademik` | `references.section.academic` | `references.view` | Tab Data Akademik |
| GET | `/references/kurikulum` | `references.section.curriculum` | `references.view` | Tab Kurikulum |
| POST | `/references/rombels/bulk-destroy` | `references.rombels.bulk-destroy` | `references.view` (+ manage) | Hapus rombel massal |
| POST | `/references/mapel/bulk-destroy` | `references.mapel.bulk-destroy` | `references.view` (+ manage) | Hapus mapel massal |
| GET | `/references/export/cp-tp/{subject}/{format}` | `references.export.cp-tp` | `references.view` | Ekspor CP/TP (Excel/Word/PDF) |
| GET | `/references/export/atp/{subject}/{format}` | `references.export.atp` | `references.view` | Ekspor ATP (Excel/Word/PDF) |
| CRUD/import | `/references/*` | `references.*` | view (+ manage untuk mutasi) | Profil sekolah, TA, semester, rombel, mapel, CP/TP/ATP |

> Dashboard wali murid = `Pages/Dashboard/WaliMurid.vue` di `/dashboard` (bukan route terpisah).

## Kontrak AI (backend service)

Service utama: `App\Services\AiDraftService`.  
Provider: tabel `ai_providers` + failover `priority_order`.  
Preferensi model per fitur: `system_settings` (`ai.model_*`).

### Env (fallback / workshop)

| Variabel | Fungsi |
|---|---|
| `AI_API_KEY` | Bearer token legacy/fallback (jangan di-commit) |
| `AI_MODEL` | default model fallback |
| `AI_API_URL` | Base URL OpenAI-compatible |
| `AI_MOCK_MODE` | `true` = tanpa panggilan jaringan |

### Input minimum (generate rencana)

```json
{
  "phase": "D",
  "grade": "7",
  "subject": "IPA",
  "topic": "Siklus Air",
  "duration_minutes": 80,
  "learning_objectives": "Menjelaskan tahapan siklus air",
  "student_needs": "Bahasa sederhana + satu refleksi",
  "curriculum_reference": "Referensi CP yang diverifikasi guru"
}
```

### Output JSON wajib (generate rencana)

- `cpDraft`, `tpDraft`, `atpDraft`
- `lessonPlanDraft` (`opening`, `core`, `closing`, `assessmentPlan`)
- `learningMaterialDraft` (`title`, `sections`, `reflectionQuestion`)
- `reviewNotes` (disarankan)

### Aturan pemanggilan

1. Hanya backend yang memanggil AI.
2. Timeout 30s, retry 2×.
3. `response_format: json_object` untuk chat completions teks.
4. Validasi schema sebelum persist ke `ai_generations`.
5. Mock mode = draf deterministik untuk workshop.
6. Co-Pilot materi: `chatRefineMaterial()` + intent create/patch/rewrite; dilarang emit `<img>` URL fiktif.
7. Media context-scoped: list/upload/delete hanya di `materials/{id}/` (ADR-008 extend).
8. Failover mengikuti `priority_order` pada `ai_providers`.
9. Jangan kirim PII siswa.

## Error & otorisasi

| Kondisi | Respons |
|---|---|
| Belum login | Redirect login |
| Permission/role tidak cocok | 403 |
| AI gagal / timeout | error aman ke user + log |
| Quiz attempt ganda | dicegah unique / UI |

## Non-kontrak

- REST JSON publik untuk mobile
- Webhook AI
- Endpoint admin multi-sekolah

Bila menambah API JSON di masa depan, dokumentasikan di sini dulu (path, payload, permission, contoh error).
