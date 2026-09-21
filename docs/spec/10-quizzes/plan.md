# Plan — Kuis

## Status

| Field | Isi |
|---|---|
| Kode | `10-quizzes` |
| Status | selesai |
| Steering | `business-rules`, ADR-005 |

## Ringkasan

Guru menyunting kuis per rencana (`/plans/{plan}/quiz`). Siswa mengerjakan sekali (`/quiz/{quiz}`) dengan skor otomatis 0–100.

## Tujuan

Penilaian singkat terhubung rencana pembelajaran.

## Acceptance

- [x] Form kuis guru + attempt siswa
- [x] Unique one-attempt per siswa
- [x] Tertutup permission `plans.manage` / `quiz.attempt`
- [x] `QuizStatus` enum konsisten (`app/Enums/QuizStatus.php`) + cast di model `Quiz` — BUG-04
- [x] Validasi `size:N` + `max:500` per jawaban di `QuizAttemptController::submit()` — GAP-03
