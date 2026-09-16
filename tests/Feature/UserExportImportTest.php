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
use App\Services\UserExportImportService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class UserExportImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    private function createSpreadsheetFile(array $headers, array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headers, ...$rows]);
        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = (string) ob_get_clean();

        return UploadedFile::fake()->createWithContent('users_test.xlsx', $content);
    }

    public function test_guest_dan_non_admin_dilarang_akses_export_import(): void
    {
        $this->get(route('users.export'))->assertRedirect(route('login'));
        $this->get(route('users.template'))->assertRedirect(route('login'));
        $this->post(route('users.import'))->assertRedirect(route('login'));

        $teacher = User::where('email', 'naya@aksara.test')->firstOrFail();

        $this->actingAs($teacher)
            ->get(route('users.export'))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('users.template'))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->post(route('users.import'))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('users.credentials-download'))
            ->assertForbidden();
    }

    public function test_admin_bisa_mengunduh_template_impor_pengguna(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get(route('users.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_bisa_mengunduh_ekspor_semua_pengguna(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get(route('users.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_bisa_mengunduh_ekspor_pengguna_dengan_filter_role(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get(route('users.export', ['role' => 'teacher']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_bisa_mengunduh_ekspor_pengguna_dengan_ids(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $teacher = User::where('email', 'naya@aksara.test')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get(route('users.export', ['ids' => [$admin->id, $teacher->id]]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_bisa_mengimpor_pengguna_baru_dengan_password_default(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $class = SchoolClass::firstOrFail();

        $file = $this->createSpreadsheetFile(
            ['No', 'Nama Lengkap', 'Email', 'Role (Peran)', 'Rombel (Nama/Kode)'],
            [
                ['1', 'Budi Santoso Import', 'budi.import@aksara.test', 'Siswa', $class->name],
                ['2', 'Citra Dewi Import', 'citra.import@aksara.test', 'Guru', ''],
            ]
        );

        $response = $this->actingAs($admin)
            ->post(route('users.import'), [
                'file' => $file,
                'duplicate_mode' => 'skip',
                'password_mode' => 'default',
                'default_password' => 'AksaraRahasia2026!',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('message');

        $userBudi = User::where('email', 'budi.import@aksara.test')->first();
        $this->assertNotNull($userBudi);
        $this->assertSame('Budi Santoso Import', $userBudi->name);
        $this->assertSame(UserRole::Student, $userBudi->role);
        $this->assertTrue(Hash::check('AksaraRahasia2026!', $userBudi->password));
        $this->assertTrue($userBudi->classes()->where('school_classes.id', $class->id)->exists());

        $userCitra = User::where('email', 'citra.import@aksara.test')->first();
        $this->assertNotNull($userCitra);
        $this->assertSame('Citra Dewi Import', $userCitra->name);
        $this->assertSame(UserRole::Teacher, $userCitra->role);
    }

    public function test_impor_pengguna_dengan_mode_generate_password_acak_dan_unduh_kredensial(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $file = $this->createSpreadsheetFile(
            ['No', 'Nama Lengkap', 'Email', 'Role (Peran)', 'Rombel (Nama/Kode)'],
            [
                ['1', 'Rian Random Pass', 'rian.random@aksara.test', 'Siswa', ''],
            ]
        );

        $response = $this->actingAs($admin)
            ->post(route('users.import'), [
                'file' => $file,
                'duplicate_mode' => 'skip',
                'password_mode' => 'generate',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('has_credentials', true);

        $userRian = User::where('email', 'rian.random@aksara.test')->first();
        $this->assertNotNull($userRian);

        $creds = session('imported_credentials');
        $this->assertIsArray($creds);
        $this->assertNotEmpty($creds);

        // Unduh kredensial yang tersimpan di sesi
        $downloadResponse = $this->actingAs($admin)
            ->withSession(['imported_credentials' => $creds])
            ->get(route('users.credentials-download'));

        $downloadResponse->assertOk();
        $downloadResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_impor_pengguna_duplicate_handling_skip_dan_update(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();
        $existingStudent = User::where('role', UserRole::Student)->firstOrFail();
        $originalName = $existingStudent->name;

        // 1. Uji mode skip: Nama di Excel tidak boleh menimpa nama di DB
        $fileSkip = $this->createSpreadsheetFile(
            ['No', 'Nama Lengkap', 'Email', 'Role (Peran)', 'Rombel (Nama/Kode)'],
            [
                ['1', 'Nama Baru Harus Diabaikan', $existingStudent->email, 'Siswa', ''],
            ]
        );

        $this->actingAs($admin)
            ->post(route('users.import'), [
                'file' => $fileSkip,
                'duplicate_mode' => 'skip',
                'password_mode' => 'default',
                'default_password' => 'AksaraRahasia2026!',
            ])
            ->assertRedirect(route('users.index'));

        $existingStudent->refresh();
        $this->assertSame($originalName, $existingStudent->name);

        // 2. Uji mode update: Nama di DB harus diperbarui sesuai Excel
        $fileUpdate = $this->createSpreadsheetFile(
            ['No', 'Nama Lengkap', 'Email', 'Role (Peran)', 'Rombel (Nama/Kode)'],
            [
                ['1', 'Nama Siswa Terupdate', $existingStudent->email, 'Siswa', ''],
            ]
        );

        $this->actingAs($admin)
            ->post(route('users.import'), [
                'file' => $fileUpdate,
                'duplicate_mode' => 'update',
                'password_mode' => 'default',
                'default_password' => 'AksaraRahasia2026!',
            ])
            ->assertRedirect(route('users.index'));

        $existingStudent->refresh();
        $this->assertSame('Nama Siswa Terupdate', $existingStudent->name);
    }

    public function test_impor_pengguna_melaporkan_error_jika_baris_tidak_valid(): void
    {
        $admin = User::where('email', 'admin@aksara.test')->firstOrFail();

        $file = $this->createSpreadsheetFile(
            ['No', 'Nama Lengkap', 'Email', 'Role (Peran)', 'Rombel (Nama/Kode)'],
            [
                ['1', '', 'tanpa.nama@aksara.test', 'Siswa', ''],
                ['2', 'Email Rusak', 'bukan-email', 'Siswa', ''],
                ['3', 'Role Salah', 'role.salah@aksara.test', 'RoleTidakDikenal', ''],
            ]
        );

        $response = $this->actingAs($admin)
            ->post(route('users.import'), [
                'file' => $file,
                'duplicate_mode' => 'skip',
                'password_mode' => 'default',
                'default_password' => 'AksaraRahasia2026!',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('import_errors');

        /** @var list<string> $errors */
        $errors = session('import_errors');
        $this->assertCount(3, $errors);
    }

    public function test_service_sanitize_cell_mencegah_formula_injection(): void
    {
        $service = app(UserExportImportService::class);

        $this->assertSame("'=SUM(1,2)", $service->sanitizeCell('=SUM(1,2)'));
        $this->assertSame("'+cmd|' /C calc'!A0", $service->sanitizeCell("+cmd|' /C calc'!A0"));
        $this->assertSame("'-100", $service->sanitizeCell('-100'));
        $this->assertSame("'@malicious", $service->sanitizeCell('@malicious'));
        $this->assertSame('Normal Text', $service->sanitizeCell('Normal Text'));
    }
}
