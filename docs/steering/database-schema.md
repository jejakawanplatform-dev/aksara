# Database Schema — Aksara

Sumber kebenaran untuk tabel, relasi, dan constraint. Perubahan skema **wajib** lewat migration.

## Ringkasan entitas

```text
users ──┬── school_classes (homeroom_teacher_id, academic_year_id)
        ├── class_members (class ↔ student)
        ├── parent_students (parent ↔ student)
        ├── learning_plans ──┬── learning_materials
        │                    ├── ai_generations
        │                    ├── attendance_records
        │                    ├── quizzes ── quiz_attempts
        │                    └── teacher_evaluations
        └── learning_events (via materials / students)

academic_years ── semesters ── learning_plans
               └── school_classes
               └── curriculum_atp_items
               └── learning_plans

subjects ── learning_plans
         └── curriculum_cps ── curriculum_tps ── curriculum_atp_items
```

## Tabel inti

### `users`

| Kolom | Catatan |
|---|---|
| `id`, `name`, `email`, `password` | Standar Breeze |
| `role` | Enum string: `admin`, `teacher`, `student`, `homeroom_teacher`, `parent` (default `student`) |
| timestamps, email verification | Standar Laravel |

Spatie Permission tables juga ada. Gate fitur memakai middleware `permission:*`; identitas dashboard/sidebar memakai `users.role` (+ helper) — lihat ADR-003/007.

### `academic_years`

| Kolom | Catatan |
|---|---|
| `name`, `code` | Contoh `2025/2026`, kode unik `2025-2026` |
| `starts_on`, `ends_on` | Opsional |
| `is_active` | Satu tahun aktif untuk demo |

### `semesters`

| Kolom | Catatan |
|---|---|
| `academic_year_id` | FK → `academic_years` |
| `name`, `code`, `number` | `Ganjil`/`ganjil`/1 atau `Genap`/`genap`/2 |
| `starts_on`, `ends_on` | Periode semester |
| `is_active` | Semester aktif (demo: Ganjil) |
| unique | (`academic_year_id`, `number`) dan (`academic_year_id`, `code`) |

### `school_classes`

| Kolom | Catatan |
|---|---|
| `academic_year_id` | FK → `academic_years` (nullable) |
| `name`, `rombel_code`, `grade` | Contoh name/rombel `VII-A`, grade 7 |
| `homeroom_teacher_id` | FK → `users` |

### `class_members`

- PK komposit: (`class_id`, `student_id`)
- Pivot siswa ↔ kelas

### `parent_students`

- PK komposit: (`parent_id`, `student_id`)
- Pivot wali murid ↔ anak

### `subjects`

| Kolom | Catatan |
|---|---|
| `name` | Unique; contoh Informatika, Matematika |
| `code`, `phase`, `jenjang`, `description` | Referensi mapel (mis. `INF`, Fase `D`, `SMP`) |

### `curriculum_cps`

| Kolom | Catatan |
|---|---|
| `subject_id`, `phase` | FK mapel + fase (mis. D) |
| `element_code`, `element_name` | Mis. `BK`, Berpikir Komputasional |
| `statement`, `source_note`, `sequence` | Isi CP + catatan sumber |
| unique | (`subject_id`, `phase`, `element_code`) |

### `curriculum_tps`

| Kolom | Catatan |
|---|---|
| `curriculum_cp_id` | FK → CP |
| `code`, `statement`, `grade`, `sequence` | Mis. `BK-VII-01` |

### `curriculum_atp_items`

| Kolom | Catatan |
|---|---|
| `subject_id`, `academic_year_id`, `semester_id`, `curriculum_tp_id` | FK |
| `grade`, `sequence`, `unit_title`, `estimated_meetings` | Alur TP |

### `learning_plans`

| Kolom | Catatan |
|---|---|
| `teacher_id`, `class_id`, `subject_id` | FK |
| `academic_year_id`, `semester_id`, `curriculum_cp_id`, `curriculum_tp_id` | FK referensi (nullable) |
| `phase`, `grade`, `topic` | Konteks pembelajaran |
| `duration_minutes` | Durasi |
| `learning_objectives`, `student_needs`, `curriculum_reference` | Input guru/AI |
| `status` | `draft` \| `reviewed` \| `published` (default `draft`) |
| soft deletes | Ya |

### `learning_materials`

| Kolom | Catatan |
|---|---|
| `plan_id` | FK → `learning_plans` |
| `content` | JSON (`title`, `sections[]` dengan `heading` + `body` HTML TipTap, `reflectionQuestion`) |
| `status` | `draft` \| `published` \| `archived` |
| `published_at` | Diisi saat publish |
| soft deletes | Ya |

**Media terkait (bukan kolom DB):** file gambar diunggah ke disk `public` path `materials/{learning_material_id}/{uuid}.{ext}`; URL publik `/storage/materials/...` disematkan di `content.sections[].body`. Symlink: `php artisan storage:link` (ADR-008).

### `system_settings`

| Kolom | Catatan |
|---|---|
| `key`, `value` | Key-value (JSON/teks); contoh preferensi `ai.model_*` |

### `ai_providers`

| Kolom | Catatan |
|---|---|
| `name`, `driver`, `api_key`, `base_url`, `model` | Konfigurasi vendor |
| `priority_order`, `is_active` | Failover router |
| Kapabilitas | Katalog kode `supports_image_generation` (OpenAI/Gemini); dipakai Co-Pilot Vue |

### `ai_usage_logs`

Jejak pemakaian token / panggilan AI untuk analitik & kuota.

### `ai_generations`

| Kolom | Catatan |
|---|---|
| `plan_id`, `created_by` | FK |
| `input_summary`, `output` | JSON |
| `model` | Nama model AI |
| `review_status` | `pending` \| `approved` \| `rejected` |
| `reviewed_at`, `reviewed_by` | Jejak review |

### `attendance_records`

| Kolom | Catatan |
|---|---|
| `plan_id`, `student_id` | Unique bersama |
| `status` | `present` \| `excused` \| `sick` \| `absent` |
| `notes` | Opsional |

### `learning_events`

| Kolom | Catatan |
|---|---|
| `material_id`, `student_id` | FK |
| `event_type` | `material_opened` \| `material_read` |
| `occurred_at` | Waktu kejadian |

### `quizzes`

| Kolom | Catatan |
|---|---|
| `plan_id` | FK |
| `title` | Judul |
| `questions` | JSON soal PG |
| `status` | `draft` \| `published` |
| soft deletes | Ya |

### `quiz_attempts`

| Kolom | Catatan |
|---|---|
| `quiz_id`, `student_id` | Unique bersama (satu attempt) |
| `answers` | JSON |
| `score` | 0–100 |
| `submitted_at` | Waktu submit |

### `teacher_evaluations`

| Kolom | Catatan |
|---|---|
| `plan_id`, `teacher_id` | Unique bersama |
| `notes`, `challenges`, `next_action` | Teks refleksi |

### `subject_teachers`

Pivot guru pengampu ↔ mapel (referensi / plotting).

## Migration terkait (inti)

| File | Isi |
|---|---|
| `0001_01_01_000000_create_users_table.php` | users, sessions |
| `2026_08_09_061707_create_permission_tables.php` | Spatie |
| `2026_08_09_061808_add_role_to_users_table.php` | `users.role` |
| `2026_08_09_061809_create_school_tables.php` | kelas, anggota, wali, mapel |
| `2026_08_09_061810_create_learning_tables.php` | plans, materials, ai_generations |
| `2026_08_09_061811_create_activity_tables.php` | attendance, events, quizzes, evaluations |
| `2026_08_09_170000_create_curriculum_reference_tables.php` | CP/TP/ATP |
| `2026_08_09_171500_create_semesters_table.php` | semesters |
| `2026_08_09_183000_create_system_settings_table.php` | settings |
| `2026_08_09_190000_create_ai_usage_logs_table.php` | usage logs |
| `2026_08_09_200000_create_ai_providers_table.php` | AI providers |
| `2026_08_10_210000_create_subject_teachers_table.php` | plotting guru–mapel |

> Daftar lengkap: `database/migrations/`. Perubahan skema **hanya** lewat migration baru.

## Data demo

Perintah: `php artisan migrate:fresh --seed` atau `php artisan aksara:seed-demo`.  
Detail akun: `docs/demo-users.md`.

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@aksara.test` | `password` |
| Guru | `naya@aksara.test` | `password` |
| Wali kelas | `arif@aksara.test` | `password` |
| Siswa | `adit@aksara.test`, `bunga@…`, … | `password` |
| Wali murid | `ortu.adit@aksara.test`, … | `password` |

Kelas demo: **VII-A / VIII-A / IX-A**; mapel demo termasuk Informatika.
