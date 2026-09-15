# Implementasi — Tahap 19 (Smoke Testing & QA Suite Enforcer)

## Lokasi Berkas Kunci

| Tipe | Berkas | Peran |
|---|---|---|
| Smoke Test | `tests/Feature/Smoke/CriticalJourneySmokeTest.php` | Pengujian skenario kritis 5 role sekolah |
| Config PHPStan | `phpstan.neon` | Konfigurasi level 9 Larastan |
| Baseline PHPStan | `phpstan-baseline.neon` | Baseline resmi generic typing framework |
| Aturan QA Agen | `docs/steering/coding-standards.md` | Bagian 7 & 8: QA Wajib & DoD per tugas |
| Strategi Uji | `docs/steering/testing-strategy.md` | Lapisan 4: Smoke Test Terpadu |
| Handoff | `docs/steering/handover.md` | Catatan status kelulusan QA dan instruksi agen |

## Alur Critical Journey Smoke Test

```text
[1. Admin]
   ├── GET /dashboard ➔ Dashboard/Admin
   ├── GET /users ➔ Users/Index
   ├── GET /settings ➔ Settings/Index
   └── GET /references ➔ References/Index
        │
        ▼
[2. Guru]
   ├── GET /dashboard ➔ Dashboard/Guru
   ├── GET /plans ➔ Plans/Index
   └── GET /materials/{id}/edit ➔ Materials/Edit
        │
        ▼
[3. Siswa]
   ├── GET /dashboard ➔ Dashboard/Siswa
   ├── GET /materials/{id} ➔ Materials/Show
   ├── Assert database: learning_events tercatat
   ├── GET /quiz/{id}/attempt ➔ Quiz/Attempt
   └── POST /quiz/{id}/attempt ➔ Assert database: quiz_attempts tercatat
        │
        ▼
[4. Guru (Lanjutan)]
   ├── POST /plans/{id}/attendance ➔ Assert database: attendance_records (hadir)
   └── POST /plans/{id}/evaluation ➔ Assert database: teacher_evaluations
        │
        ▼
[5. Wali Kelas]
   ├── GET /dashboard ➔ Dashboard/WaliKelas
   └── GET /attendance/summary ➔ Attendance/Summary
        │
        ▼
[6. Wali Murid]
   └── GET /dashboard ➔ Dashboard/WaliMurid (has 'childData')
```
