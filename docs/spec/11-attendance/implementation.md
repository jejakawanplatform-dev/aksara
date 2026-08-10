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
