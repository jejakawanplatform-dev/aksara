# Verification — Kuis

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Controllers | `Plans/PlanQuizController`, `Quiz/QuizAttemptController` |
| Pages | `resources/js/Pages/Quiz/{Form,Attempt}.vue` |
| Models | `Quiz`, `QuizAttempt` |
| Routes | `plans.quiz*`, `quiz.attempt*` |
| Test | `tests/Feature/LearningPipelineTest.php` |

## Checklist

- [x] Form guru (`plans.manage`) + attempt siswa (`quiz.attempt`)
- [x] Satu attempt per siswa
- [x] Pipeline test menyentuh kuis

## Perintah

```bash
php artisan test --filter=LearningPipelineTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Sunting kuis | guru | tersimpan |
| Kerjakan kuis 2× | siswa | kedua ditolak |
