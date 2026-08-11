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

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminOversightTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_admin_bisa_melihat_seluruh_rencana_pembelajaran_guru(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('plans.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Plans/Index')
                ->where('isAdmin', true)
                ->has('plans.data')
            );
    }

    public function test_admin_bisa_memfilter_rencana_berdasarkan_guru(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $guruNaya = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('plans.index', ['teacher' => $guruNaya->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Plans/Index')
                ->where('filters.teacher', (string) $guruNaya->id)
            );
    }

    public function test_admin_bisa_mengakses_halaman_supervisi_evaluasi_guru(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('evaluations.monitoring'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Evaluation/Monitoring')
                ->where('isAdmin', true)
            );
    }
}
