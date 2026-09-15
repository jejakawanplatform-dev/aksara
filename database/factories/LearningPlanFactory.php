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

namespace Database\Factories;

use App\Enums\PlanStatus;
use App\Models\LearningPlan;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningPlan>
 */
class LearningPlanFactory extends Factory
{
    protected $model = LearningPlan::class;

    public function definition(): array
    {
        // Gunakan SchoolClass & Subject yang sudah ada, atau buat yang baru secara inline
        $classId = SchoolClass::query()->value('id')
            ?? SchoolClass::query()->create([
                'name'  => 'VII-A',
                'grade' => 7,
            ])->id;

        $subjectId = Subject::query()->value('id')
            ?? Subject::query()->create([
                'name'  => 'Informatika',
                'code'  => 'INF',
                'phase' => 'D',
                'jenjang' => 'SMP',
            ])->id;

        return [
            'teacher_id'           => User::factory(),
            'academic_year_id'     => null,
            'semester_id'          => null,
            'class_id'             => $classId,
            'subject_id'           => $subjectId,
            'curriculum_cp_id'     => null,
            'curriculum_tp_id'     => null,
            'phase'                => fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
            'grade'                => fake()->numberBetween(1, 12),
            'topic'                => fake()->sentence(4),
            'duration_minutes'     => fake()->randomElement([40, 60, 80, 90]),
            'learning_objectives'  => fake()->paragraph(),
            'student_needs'        => null,
            'curriculum_reference' => '-',
            'status'               => PlanStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => PlanStatus::Published]);
    }

    public function draft(): static
    {
        return $this->state(['status' => PlanStatus::Draft]);
    }
}
