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
| GET | `/access` | `access.index` | `access.manage` | `Access\AccessController` → `Access/Index` |
| PUT | `/access` | `access.save` | `access.manage` | simpan matrix |
| POST | `/access/reset-defaults` | `access.reset-defaults` | `access.manage` | reset matrix |
| GET | `/settings` | `settings.index` | `settings.manage` | `Settings\SettingsController` → `Settings/Index` |

Mutasi users/settings (POST/PUT/DELETE providers, dll.) di group yang sama — lihat `routes/web.php`.

### Guru — rencana pembelajaran

| Method | Path | Nama | Permission | Controller → page |
|---|---|---|---|---|
| GET | `/plans` | `plans.index` | `plans.manage` | `Plans\PlanController@index` → `Plans/Index` |
| GET | `/plans/create` | `plans.create` | `plans.manage` | → `Plans/Create` |
| GET | `/plans/{plan}/edit` | `plans.edit` | `plans.manage` | → `Plans/Edit` |
| GET | `/plans/{plan}/draft` | `plans.draft` | `plans.manage` | → `Plans/Draft` |
| POST | `/plans/{plan}/draft/approve` | `plans.draft.approve` | `plans.manage` | approve draf AI |
| POST | `/plans/{plan}/draft/publish` | `plans.draft.publish` | `plans.manage` | publish plan+materi |
| GET/POST | `/plans/{plan}/quiz` | `plans.quiz*` | `plans.manage` | → `Quiz/Form` |
| GET | `/plans/export/{format}` | `plans.export` | `plans.manage` | Excel/Word/PDF (Blade export) |

### Materi & Co-Pilot

| Method | Path | Nama | Permission | Catatan |
|---|---|---|---|---|
| GET | `/materials` | `materials.index` | `materials.read` \| `plans.manage` | `Materials/Index` |
| GET | `/materials/{material}` | `materials.show` | sama | `Materials/Show` (+ learning event) |
| GET | `/materials/{material}/edit` | `materials.edit` | sama | `Materials/Edit` + TipTap Vue |
| PUT | `/materials/{material}` | `materials.update` | sama | simpan konten JSON |
| POST | `/materials/{material}/publish` | `materials.publish` | sama | publish materi |
| POST | `/materials/{material}/images` | `materials.images` | sama | upload → `public/materials/{id}/` |
| POST | `/materials/{material}/copilot` | `materials.copilot` | sama | JSON Co-Pilot (`chatRefineMaterial`) |

### Kehadiran, evaluasi, laporan, kuis siswa

| Method | Path | Nama | Permission | Page |
|---|---|---|---|---|
| GET/POST | `/plans/{plan}/attendance` | `attendance.*` | `attendance.manage` | `Attendance/Form` |
| GET | `/attendance/summary` | `attendance.summary` | `attendance.summary` | `Attendance/Summary` |
| GET/POST | `/plans/{plan}/evaluation` | `evaluation.*` | `evaluation.manage` | `Evaluation/Form` |
| GET | `/evaluations/monitoring` | `evaluations.monitoring` | `evaluation.manage` | `Evaluation/Monitoring` |
| GET | `/reports/guru` | `reports.guru` | `reports.teacher` | `Reports/Teacher` |
| GET/POST | `/quiz/{quiz}` | `quiz.attempt*` | `quiz.attempt` | `Quiz/Attempt` |

### Referensi kurikulum

| Method | Path | Nama | Permission | Page |
|---|---|---|---|---|
| GET | `/references` | `references.index` | `references.view` | `References/Index` |
| CRUD/import/export | `/references/*` | `references.*` | view (+ manage untuk mutasi) | hub yang sama; export PDF via Blade |

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
7. Upload gambar: `POST materials/{id}/images` → disk `public` `materials/{id}/`.
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
