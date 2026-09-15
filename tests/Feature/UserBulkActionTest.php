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
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBulkActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_admin_bisa_bulk_delete_pengguna(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $userA = User::create([
            'name' => 'Siswa Dummy A',
            'email' => 'dummy.a@aksara.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Student,
        ]);
        $userB = User::create([
            'name' => 'Siswa Dummy B',
            'email' => 'dummy.b@aksara.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Student,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('users.bulk-destroy'), [
                'ids' => [$userA->id, $userB->id],
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('message');

        $this->assertDatabaseMissing('users', ['id' => $userA->id]);
        $this->assertDatabaseMissing('users', ['id' => $userB->id]);
    }

    public function test_admin_tidak_bisa_bulk_delete_akun_sendiri(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $dummy = User::create([
            'name' => 'Siswa Dummy X',
            'email' => 'dummy.x@aksara.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Student,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('users.bulk-destroy'), [
                'ids' => [$admin->id, $dummy->id],
            ]);

        $response->assertRedirect(route('users.index'));

        // Akun admin tetap ada di database
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        // Dummy berhasil dihapus
        $this->assertDatabaseMissing('users', ['id' => $dummy->id]);
    }

    public function test_guru_dengan_rencana_aktif_dilewati_saat_bulk_delete(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $guruNaya = User::where('email', 'naya@aksara.test')->firstOrFail(); // Guru dengan RPP demo

        $dummy = User::create([
            'name' => 'Siswa Dummy Y',
            'email' => 'dummy.y@aksara.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Student,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('users.bulk-destroy'), [
                'ids' => [$guruNaya->id, $dummy->id],
            ]);

        $response->assertRedirect(route('users.index'));

        // Guru Naya tetap ada karena punya learning plans
        $this->assertDatabaseHas('users', ['id' => $guruNaya->id]);
        // Dummy dihapus
        $this->assertDatabaseMissing('users', ['id' => $dummy->id]);
    }

    public function test_wali_kelas_terikat_rombel_dilewati_saat_bulk_delete(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $waliArif = User::where('email', 'arif@aksara.test')->firstOrFail(); // Wali kelas VII-A

        $response = $this->actingAs($admin)
            ->post(route('users.bulk-destroy'), [
                'ids' => [$waliArif->id],
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('error');

        // Wali kelas Arif tetap ada
        $this->assertDatabaseHas('users', ['id' => $waliArif->id]);
    }

    public function test_non_admin_dilarang_bulk_delete(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $dummy = User::create([
            'name' => 'Target Siswa',
            'email' => 'target@aksara.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Student,
        ]);

        $this->actingAs($guru)
            ->post(route('users.bulk-destroy'), [
                'ids' => [$dummy->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $dummy->id]);
    }

    public function test_admin_bisa_bulk_delete_mode_select_all_matching(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $student1 = User::create([
            'name' => 'Tes Matching 1',
            'email' => 'match1@aksara.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Student,
        ]);
        $student2 = User::create([
            'name' => 'Tes Matching 2',
            'email' => 'match2@aksara.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Student,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('users.bulk-destroy'), [
                'select_all_matching' => true,
                'search' => 'Tes Matching',
            ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $student1->id]);
        $this->assertDatabaseMissing('users', ['id' => $student2->id]);
    }
}
