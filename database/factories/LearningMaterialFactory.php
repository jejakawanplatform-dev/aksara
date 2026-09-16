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

use App\Enums\MaterialStatus;
use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningMaterial>
 */
class LearningMaterialFactory extends Factory
{
    protected $model = LearningMaterial::class;

    public function definition(): array
    {
        return [
            'plan_id' => LearningPlan::factory(),
            'content' => [
                'title' => fake()->sentence(3),
                'sections' => [
                    ['heading' => 'Pengantar', 'body' => '<p>'.fake()->paragraph().'</p>'],
                ],
                'reflectionQuestion' => fake()->sentence().'?',
            ],
            'status' => MaterialStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status' => MaterialStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state([
            'status' => MaterialStatus::Draft,
            'published_at' => null,
        ]);
    }
}
