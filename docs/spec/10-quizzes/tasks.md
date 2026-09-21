# Tasks — Kuis

| ID | Task | Prioritas | Status | Catatan |
|---|---|---|---|---|
| T01 | Model `Quiz` + `QuizAttempt` + migration | P0 | done | |
| T02 | `PlanQuizController` edit/store | P0 | done | |
| T03 | `QuizAttemptController` show/submit | P0 | done | |
| T04 | Pages `Quiz/Form.vue`, `Quiz/Attempt.vue` | P0 | done | |
| T05 | Skor otomatis dari `correct_answer` | P0 | done | |
| T06 | Unique (quiz_id, student_id) | P0 | done | ADR-005 |
| T07 | Coverage di `LearningPipelineTest` | P1 | done | |
| T08 | Simpan kuis by id (bukan updateOrCreate title) | P2 | done | |
| T09 | Feature test Quiz khusus | P2 | done | `PlanQuizTest` |
| T10 | **[HOTFIX]** Buat `QuizStatus` enum + cast di model `Quiz` | P1 | done | BUG-04 — Audit 2026-09-21; status masih raw string menyebabkan triple-fallback detection di `MaterialController::show()` |
| T11 | **[HOTFIX]** Validasi `size:N` + `max:500` pada `QuizAttemptController::submit()` | P2 | done | GAP-03 — Audit 2026-09-21; saat ini tidak ada batas jumlah/panjang jawaban yang dikirim siswa |
