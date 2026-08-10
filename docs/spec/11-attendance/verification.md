# Verification — Kehadiran

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Controllers | `Attendance/AttendanceController`, `AttendanceSummaryController` |
| Pages | `resources/js/Pages/Attendance/{Form,Summary}.vue` |
| Model/Enum | `AttendanceRecord`, `AttendanceStatus` |
| Routes | `attendance.form`, `attendance.save`, `attendance.summary` |

## Checklist

- [x] Upsert unik `(plan_id, student_id)`
- [x] Permission manage vs summary terpisah
- [x] Wali kelas hanya kelas homeroom; `classId`/`planId` di luar scope → 403
- [x] Filter rencana di halaman summary

## Perintah

```bash
php artisan test --filter=AttendanceSummaryTest
php artisan test --filter=AttendanceEvaluationInertiaTest
php artisan test --filter=LearningPipelineTest
php artisan test --filter=AdminOversightTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Isi absensi | guru | tersimpan |
| Summary | wali/guru | rekap tampil |
