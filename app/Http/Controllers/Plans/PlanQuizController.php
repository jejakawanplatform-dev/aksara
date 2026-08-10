<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Models\LearningPlan;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PlanQuizController extends Controller
{
    public function edit(LearningPlan $plan): Response
    {
        abort_unless($plan->teacher_id === Auth::id(), 403);

        $plan->load(['subject', 'class']);
        $existing = $plan->quizzes()->latest()->get();

        $defaults = [
            'title' => 'Kuis: '.$plan->topic,
            'questions' => [
                [
                    'question' => '',
                    'options' => ['', '', '', ''],
                    'correct_answer' => '',
                ],
            ],
            'status' => 'draft',
        ];

        if ($existing->isNotEmpty()) {
            $latest = $existing->first();
            $defaults = [
                'title' => $latest->title,
                'questions' => is_array($latest->questions) && count($latest->questions) > 0
                    ? $latest->questions
                    : $defaults['questions'],
                'status' => $latest->status ?? 'draft',
            ];
        }

        return Inertia::render('Quiz/Form', [
            'plan' => [
                'id' => $plan->id,
                'topic' => $plan->topic,
                'subject' => $plan->subject?->name,
                'className' => $plan->class?->name,
                'grade' => $plan->grade,
            ],
            'existingQuizzes' => $existing->map(fn (Quiz $q) => [
                'id' => $q->id,
                'title' => $q->title,
                'status' => $q->status,
                'questionCount' => is_array($q->questions) ? count($q->questions) : 0,
            ]),
            'form' => $defaults,
            'storeUrl' => route('plans.quiz.store', $plan),
            'indexUrl' => route('plans.index'),
        ]);
    }

    public function store(Request $request, LearningPlan $plan): RedirectResponse
    {
        abort_unless($plan->teacher_id === Auth::id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|min:3|max:255',
            'status' => 'required|in:draft,published',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string|min:3',
            'questions.*.options' => 'required|array|size:4',
            'questions.*.options.*' => 'required|string|min:1',
            'questions.*.correct_answer' => 'required|string',
        ]);

        foreach ($validated['questions'] as $i => $q) {
            if (! in_array($q['correct_answer'], $q['options'], true)) {
                return back()
                    ->withInput()
                    ->withErrors(["questions.$i.correct_answer" => 'Jawaban benar harus salah satu opsi.']);
            }
        }

        Quiz::updateOrCreate(
            ['plan_id' => $plan->id, 'title' => $validated['title']],
            [
                'questions' => array_values($validated['questions']),
                'status' => $validated['status'],
            ]
        );

        $message = $validated['status'] === 'published'
            ? 'Kuis diterbitkan. Siswa dapat mengerjakan.'
            : 'Kuis disimpan sebagai draf.';

        return back()->with('message', $message);
    }
}
