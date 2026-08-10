# Verification — Dashboard & laporan

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Controllers | `Dashboard/DashboardController`, `WaliMuridController`, `Reports/TeacherReportController` |
| Pages | `resources/js/Pages/Dashboard/{Admin,Guru,Siswa,WaliKelas,WaliMurid,Generic}.vue` |
| Laporan | `resources/js/Pages/Reports/Teacher.vue` |
| Routes | `dashboard`, `reports.guru` |
| Tests | `AksaraTest`, `AdminOversightTest` |

## Checklist

- [x] Dispatch page per `users.role`
- [x] Laporan `permission:reports.teacher`

## Perintah

```bash
php artisan test --filter=AksaraTest
php artisan test --filter=AdminOversightTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Login multi-role | sesuai | dashboard berbeda |
| `/reports/guru` | siswa | 403 |
