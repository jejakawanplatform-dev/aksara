# Implementation — Kehadiran

## Artefak

| Area | Path |
|---|---|
| Controllers | `Attendance/AttendanceController`, `AttendanceSummaryController`, `AttendanceExportController` |
| Pages | `Pages/Attendance/{Form,Summary}.vue` (Tandai Semua Hadir, Live Counters, Early Warning <75%) |
| Services | `AttendanceExportService` |
| Exports | `resources/views/exports/attendance-pdf.blade.php` (PDF Landscape), Excel (.xlsx) |
| Model/Enum | `AttendanceRecord`, `AttendanceStatus` |
| Routes | `attendance.form`, `attendance.save`, `attendance.summary`, `attendance.export` |

## Otorisasi

- Form/save: `permission:attendance.manage`
- Summary & Ekspor: `permission:attendance.summary`
- Scope kelas: wali → `homeroom_teacher_id`; guru mapel → kelas dari rencana miliknya; selain itu (mis. admin override) → semua
- Scope rencana: wali/admin → semua di kelas; guru mapel → miliknya saja
- `classId` / `planId` di luar scope → HTTP 403
