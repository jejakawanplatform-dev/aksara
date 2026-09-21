<?php

/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 */

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\LearningPlan;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
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
        abort_unless($user instanceof User, 401);

        $quiz->load('plan');
        $plan = $quiz->plan;
        abort_unless($plan instanceof LearningPlan, 404);

        abort_unless($user->isStudent(), 403);
        abort_unless($quiz->isPublished(), 403);
        abort_unless($user->belongsToClass($plan->class_id), 403);

        $existing = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $user->id)
            ->first();

        $justSubmitted = (bool) session()->pull('quiz_just_submitted', false);

        /** @var array<int|string, mixed> $rawQuestions */
        $rawQuestions = is_array($quiz->questions) ? $quiz->questions : [];
        $questions = collect($rawQuestions)->map(function ($q, $i) {
            $questionText = is_array($q) && isset($q['question']) && is_scalar($q['question']) ? (string) $q['question'] : '';
            $rawOptions = is_array($q) && isset($q['options']) && is_array($q['options']) ? $q['options'] : [];
            $options = [];
            foreach ($rawOptions as $opt) {
                if (is_scalar($opt)) {
                    $options[] = (string) $opt;
                }
            }

            return [
                'index' => $i,
                'question' => $questionText,
                'options' => $options,
            ];
        })->values();

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
        abort_unless($user instanceof User, 401);

        $quiz->load('plan');
        $plan = $quiz->plan;
        abort_unless($plan instanceof LearningPlan, 404);

        abort_unless($user->isStudent(), 403);
        abort_unless($quiz->isPublished(), 403);
        abort_unless($user->belongsToClass($plan->class_id), 403);

        $existing = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $user->id)
            ->first();

        if ($existing) {
            return back()->with('message', 'Kamu sudah mengerjakan quiz ini sebelumnya.');
        }

        $rawQuestions = is_array($quiz->questions) ? $quiz->questions : [];
        $total = count($rawQuestions);

        $validated = $request->validate([
            'answers' => ['required', 'array', 'size:' . $total],
            'answers.*' => ['nullable', 'string', 'max:500'],
        ]);

        $answers = $validated['answers'];
        $correct = 0;

        foreach ($rawQuestions as $i => $question) {
            $expected = is_array($question) && isset($question['correct_answer']) && is_string($question['correct_answer'])
                ? $question['correct_answer']
                : '__none__';
            if (($answers[$i] ?? '') === $expected) {
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
