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
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_admin_bisa_akses_users(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Users/Index')
                ->where('pageTitle', 'Manajemen Pengguna')
            );
    }

    public function test_guru_tidak_bisa_akses_users(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($guru)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_admin_bisa_buat_user_dan_sync_spatie(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Guru Baru',
                'email' => 'guru.baru@aksara.test',
                'role' => UserRole::Teacher->value,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'guru.baru@aksara.test')->firstOrFail();
        $this->assertTrue($user->isTeacher());
        $this->assertTrue($user->hasRole('teacher'));
    }

    public function test_admin_bisa_taut_siswa_ke_rombel(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $siswa = User::where('email', 'eka@aksara.test')->firstOrFail();
        $viii = SchoolClass::where('rombel_code', 'VIII-A')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('users.attach-class', $siswa), [
                'class_id' => $viii->id,
            ])
            ->assertRedirect();

        $this->assertTrue($siswa->fresh()->classes()->where('school_classes.id', $viii->id)->exists());
    }

    public function test_admin_tidak_bisa_hapus_diri_sendiri(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('error', 'Tidak bisa menghapus akun sendiri.');

        $this->assertDatabaseHas('users', ['email' => 'admin@aksara.test']);
    }

    public function test_admin_bisa_buka_referensi(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('references.index'))
            ->assertOk();
    }
}
