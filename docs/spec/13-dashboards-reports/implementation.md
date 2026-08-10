# Implementation — Dashboard & laporan

## Artefak

| Area | Path |
|---|---|
| Controllers | `Dashboard/DashboardController`, `Dashboard/WaliMuridController`, `Reports/TeacherReportController` |
| Pages | `Pages/Dashboard/*`, `Pages/Reports/Teacher.vue` |
| Route | `dashboard` (`auth`,`verified`); `reports.guru` (`reports.teacher`) |
| Tests | `AksaraTest`, `AdminOversightTest` |

## Otorisasi

- Dashboard: session auth (isi tergantung role)
- Laporan: `permission:reports.teacher`
- Wali kelas: ringkasan kelas homeroom (kehadiran, materi terbit, kuis, siswa perlu perhatian) — mode baca; deep-link ke `attendance.summary?classId=`
