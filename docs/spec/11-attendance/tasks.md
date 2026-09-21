# Tasks — Kehadiran

| ID | Task | Prioritas | Status | Catatan |
|---|---|---|---|---|
| T01 | Model `AttendanceRecord` + enum status | P0 | done | |
| T02 | `AttendanceController` edit/save | P0 | done | |
| T03 | `AttendanceSummaryController` index | P0 | done | |
| T04 | Pages Form + Summary | P0 | done | |
| T05 | Unique (plan_id, student_id) upsert | P0 | done | |
| T06 | Permissions manage/summary | P0 | done | |
| T07 | Coverage pipeline / oversight | P1 | done | |
| T08 | Perkuat akses wali kelas ke summary | P2 | done | scope kelas + plan filter + 403 |
| T09 | Fitur Ekspor Rekap Kehadiran PDF & Excel | P1 | done | `AttendanceExportService` + `AttendanceExportController` |
| T10 | Peningkatan UX Form Presensi & Early Warning | P1 | done | Tandai Semua Hadir, Live Counters, badge < 75% |
| T11 | **[HOTFIX]** Izinkan Admin mengakses/mengedit absensi semua guru | P2 | done | GAP-02 — Audit 2026-09-21; `AttendanceController::edit/save` hanya izinkan owner guru, admin di-abort 403 |
