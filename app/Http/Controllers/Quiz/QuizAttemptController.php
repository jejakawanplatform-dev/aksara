<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class QuizAttemptController extends Controller
{
    public function show(Quiz $quiz): Response
    {
        $user = Auth::user();

        abort_unless($user->isStudent(), 403);
        abort_unless($quiz->isPublished(), 403);
        abort_unless($user->belongsToClass($quiz->plan->class_id), 403);

        $quiz->load('plan');

        $existing = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $user->id)
            ->first();

        $justSubmitted = (bool) session()->pull('quiz_just_submitted', false);

        $questions = collect($quiz->questions ?? [])->map(fn ($q, $i) => [
            'index' => $i,
            'question' => $q['question'] ?? '',
            'options' => array_values($q['options'] ?? []),
        ])->values();

        return Inertia::render('Quiz/Attempt', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'questionCount' => $questions->count(),
            ],
            'questions' => $questions,
            'alreadyDone' => (bool) $existing && ! $justSubmitted,
            'submitted' => (bool) $existing,
            'score' => $existing?->score,
            'answers' => $existing !== null ? ($existing->answers ?? []) : [],
            'submitUrl' => route('quiz.attempt.submit', $quiz),
        ]);
    }

    public function submit(Request $request, Quiz $quiz): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user->isStudent(), 403);
        abort_unless($quiz->isPublished(), 403);
        abort_unless($user->belongsToClass($quiz->plan->class_id), 403);

        $existing = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $user->id)
            ->first();

        if ($existing) {
            return back()->with('message', 'Kamu sudah mengerjakan quiz ini sebelumnya.');
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string'],
        ]);

        $answers = $validated['answers'];
        $total = count($quiz->questions ?? []);
        $correct = 0;

        foreach ($quiz->questions ?? [] as $i => $question) {
            if (($answers[$i] ?? '') === ($question['correct_answer'] ?? '__none__')) {
                $correct++;
            }
        }

        $score = $total > 0 ? (int) round(($correct / $total) * 100) : 0;

        try {
            QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $user->id,
                'answers' => $answers,
                'score' => $score,
                'submitted_at' => now(),
            ]);
        } catch (QueryException) {
            return back()->with('message', 'Kamu sudah mengerjakan quiz ini sebelumnya.');
        }

        return back()
            ->with('message', "Quiz selesai! Nilai kamu: {$score}")
            ->with('quiz_just_submitted', true);
    }
}
