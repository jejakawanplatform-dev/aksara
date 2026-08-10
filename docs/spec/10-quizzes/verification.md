# Verification — Kuis

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Controllers | `Plans/PlanQuizController`, `Quiz/QuizAttemptController` |
| Pages | `resources/js/Pages/Quiz/{Form,Attempt}.vue` |
| Models | `Quiz`, `QuizAttempt` |
| Routes | `plans.quiz*`, `quiz.attempt*` |
| Tests | `LearningPipelineTest`, `PlanQuizTest` |

## Checklist

- [x] Form guru (`plans.manage`) + attempt siswa (`quiz.attempt`)
- [x] Satu attempt per siswa
- [x] Simpan/update by `id` (bukan title)
- [x] Pipeline + `PlanQuizTest` hijau

## Perintah

```bash
php artisan test --filter=LearningPipelineTest
php artisan test --filter=PlanQuizTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Sunting kuis + ganti judul | guru | entri sama (id) ter-update |
| + Kuis baru | guru | entri baru |
| Kerjakan kuis 2× | siswa | kedua ditolak |
