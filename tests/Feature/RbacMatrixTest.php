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

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Access\PermissionCatalog;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RbacMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_admin_bisa_akses_matrix(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('access.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Access/Index')
                ->where('pageTitle', 'Hak Akses (RBAC)')
            );
    }

    public function test_guru_tidak_bisa_akses_matrix(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($guru)
            ->get(route('access.index'))
            ->assertForbidden();
    }

    public function test_guru_punya_plans_manage_bisa_akses_rencana(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $this->assertTrue($guru->can(PermissionCatalog::PLANS_MANAGE));

        $this->actingAs($guru)
            ->get(route('plans.index'))
            ->assertOk();
    }

    public function test_cabut_plans_manage_blokir_route_guru(): void
    {
        $role = Role::findByName(UserRole::Teacher->value, 'web');
        $role->revokePermissionTo(PermissionCatalog::PLANS_MANAGE);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($guru)
            ->get(route('plans.index'))
            ->assertForbidden();
    }

    public function test_tidak_bisa_cabut_permission_wajib_admin(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $matrix = [];
        foreach (UserRole::cases() as $roleEnum) {
            $role = Role::findOrCreate($roleEnum->value, 'web');
            $owned = $role->permissions->pluck('name')->all();
            foreach (PermissionCatalog::names() as $perm) {
                $matrix[$roleEnum->value][PermissionCatalog::wireKey($perm)] = in_array($perm, $owned, true);
            }
        }
        $matrix[UserRole::Admin->value][PermissionCatalog::wireKey(PermissionCatalog::USERS_MANAGE)] = false;

        $this->actingAs($admin)
            ->put(route('access.save'), ['matrix' => $matrix])
            ->assertRedirect()
            ->assertSessionHas('error', 'Permission wajib admin tidak boleh dicabut: '.PermissionCatalog::USERS_MANAGE);

        $role = Role::findByName(UserRole::Admin->value, 'web');
        $this->assertTrue($role->hasPermissionTo(PermissionCatalog::USERS_MANAGE));
    }

    public function test_reset_default_mengembalikan_matrix(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $role = Role::findByName(UserRole::Teacher->value, 'web');
        $role->revokePermissionTo(PermissionCatalog::ATTENDANCE_MANAGE);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->actingAs($admin)
            ->post(route('access.reset-defaults'))
            ->assertRedirect()
            ->assertSessionHas('message', 'Matrix dikembalikan ke default katalog.');

        $this->assertTrue($role->fresh()->hasPermissionTo(PermissionCatalog::ATTENDANCE_MANAGE));
    }
}
