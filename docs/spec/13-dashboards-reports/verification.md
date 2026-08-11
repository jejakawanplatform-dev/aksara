# Verification — Dashboard & laporan

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Controllers | `Dashboard/DashboardController`, `WaliMuridController`, `Reports/TeacherReportController` |
| Pages | `resources/js/Pages/Dashboard/{Admin,Guru,Siswa,WaliKelas,WaliMurid,Generic}.vue` |
| Laporan | `resources/js/Pages/Reports/Teacher.vue` |
| Routes | `dashboard`, `reports.guru` |
| Tests | `AksaraTest`, `AdminOversightTest`, `HomeroomDashboardTest` |

## Checklist

- [x] Dispatch page per `users.role`
- [x] Laporan `permission:reports.teacher`
- [x] Dashboard wali kelas: metrik + rekap per kelas + link absensi
- [x] Dashboard admin Operations Console: hero + role counts + konten + pintasan

## Perintah

```bash
php artisan test --filter=AksaraTest
php artisan test --filter=HomeroomDashboardTest
php artisan test --filter=AdminOversightTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Login multi-role | sesuai | dashboard berbeda |
| `/dashboard` admin | admin@aksara.test | hero; infografis donut peran + bar konten; pintasan tanpa deretan kartu |
| `/reports/guru` | siswa | 403 |
