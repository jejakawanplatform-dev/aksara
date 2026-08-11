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

namespace App\Http\Controllers\Evaluation;

use App\Http\Controllers\Controller;
use App\Models\LearningPlan;
use App\Models\TeacherEvaluation;
use App\Support\SubjectContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationController extends Controller
{
    public function edit(LearningPlan $plan): Response
    {
        abort_unless($plan->teacher_id === Auth::id(), 403);

        $plan->load(['subject', 'class']);

        $existing = TeacherEvaluation::where('plan_id', $plan->id)
            ->where('teacher_id', Auth::id())
            ->first();

        return Inertia::render('Evaluation/Form', [
            'plan' => [
                'id' => $plan->id,
                'topic' => $plan->topic,
                'subject' => $plan->subject?->name,
                'className' => $plan->class?->name,
            ],
            'form' => [
                'notes' => $existing !== null ? (string) $existing->notes : '',
                'challenges' => $existing !== null ? (string) $existing->challenges : '',
                'next_action' => $existing !== null ? (string) $existing->next_action : '',
            ],
            'isStem' => SubjectContext::isStem($plan->subject),
            'saveUrl' => route('evaluation.save', $plan),
            'plansUrl' => route('plans.index'),
        ]);
    }

    public function save(Request $request, LearningPlan $plan): RedirectResponse
    {
        abort_unless($plan->teacher_id === Auth::id(), 403);

        $validated = $request->validate([
            'notes' => ['required', 'string', 'min:20'],
            'challenges' => ['required', 'string', 'min:10'],
            'next_action' => ['required', 'string', 'min:10'],
        ]);

        TeacherEvaluation::updateOrCreate(
            ['plan_id' => $plan->id, 'teacher_id' => Auth::id()],
            [
                'notes' => $validated['notes'],
                'challenges' => $validated['challenges'],
                'next_action' => $validated['next_action'],
            ]
        );

        return back()->with('message', 'Evaluasi & Refleksi Guru Berhasil Disimpan!');
    }
}
