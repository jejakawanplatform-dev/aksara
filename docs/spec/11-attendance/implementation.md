# Implementation — Kehadiran

## Artefak

| Area | Path |
|---|---|
| Controllers | `Attendance/AttendanceController`, `AttendanceSummaryController` |
| Pages | `Pages/Attendance/{Form,Summary}.vue` |
| Model/Enum | `AttendanceRecord`, `AttendanceStatus` |
| Routes | `attendance.form`, `attendance.save`, `attendance.summary` |

## Otorisasi

- Form/save: `permission:attendance.manage`
- Summary: `permission:attendance.summary`
- Scope kelas: wali → `homeroom_teacher_id`; guru mapel → kelas dari rencana miliknya; selain itu (mis. admin override) → semua
- Scope rencana: wali/admin → semua di kelas; guru mapel → miliknya saja
- `classId` / `planId` di luar scope → HTTP 403
