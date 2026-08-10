# Plan — Dashboard & laporan

## Status

| Field | Isi |
|---|---|
| Kode | `13-dashboards-reports` |
| Status | selesai / aktif |
| Steering | `business-rules`, `api-contract` |

## Ringkasan

`/dashboard` memilih page per `users.role` (admin/guru/siswa/wali kelas/wali murid). Laporan guru di `/reports/guru`.

## Tujuan

Ringkasan peran-spesifik + laporan aktivitas mengajar.

## Acceptance

- [x] Dispatch dashboard per role
- [x] Laporan guru permission-gated
- [x] Wali murid hanya data anak terhubung
- [x] Dashboard wali kelas memuat ringkasan kelas yang diampu
