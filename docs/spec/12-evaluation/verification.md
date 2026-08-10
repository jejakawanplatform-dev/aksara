# Verification — Evaluasi & monitoring

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Controllers | `Evaluation/EvaluationController`, `EvaluationMonitoringController` |
| Pages | `resources/js/Pages/Evaluation/{Form,Monitoring}.vue` |
| Model | `TeacherEvaluation` |
| Routes | `evaluation.*`, `evaluations.monitoring` |
| Test | `AdminOversightTest`, `AttendanceEvaluationInertiaTest` |

## Checklist

- [x] Form refleksi + monitoring hub
- [x] `permission:evaluation.manage`
- [x] Oversight test hijau
- [x] Form evaluasi assertInertia

## Perintah

```bash
php artisan test --filter=AdminOversightTest
php artisan test --filter=AttendanceEvaluationInertiaTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Isi evaluasi | guru | tersimpan |
| `/evaluations/monitoring` | admin | daftar tampil |
