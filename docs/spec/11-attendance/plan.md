# Plan — Kehadiran

## Status

| Field | Isi |
|---|---|
| Kode | `11-attendance` |
| Status | selesai / aktif |
| Steering | `business-rules` |

## Ringkasan

Guru mengisi absensi per rencana; ringkasan kehadiran tersedia untuk role dengan `attendance.summary`.

## Tujuan

Satu record unik per `(plan_id, student_id)` dengan status present/excused/sick/absent.

## Acceptance

- [x] Form isi + save upsert
- [x] Halaman summary
- [x] Permission terpisah manage vs summary
