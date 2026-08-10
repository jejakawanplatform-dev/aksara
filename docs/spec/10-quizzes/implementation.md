# Implementation — Kuis

## Artefak

| Area | Path |
|---|---|
| Controllers | `Plans/PlanQuizController`, `Quiz/QuizAttemptController` |
| Pages | `Pages/Quiz/{Form,Attempt}.vue` |
| Models | `Quiz`, `QuizAttempt` |
| Routes | `plans.quiz`, `plans.quiz.store`, `quiz.attempt`, `quiz.attempt.submit` |
| Test | `tests/Feature/LearningPipelineTest.php` |

## Otorisasi

- Form: `permission:plans.manage`
- Attempt: `permission:quiz.attempt`
