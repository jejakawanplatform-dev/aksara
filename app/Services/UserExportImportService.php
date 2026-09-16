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

namespace App\Services;

use App\Enums\UserRole;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Spatie\Permission\Models\Role;

class UserExportImportService
{
    /**
     * Sanitasi nilai sel untuk mencegah spreadsheet formula injection (=, +, -, @).
     */
    public function sanitizeCell(mixed $value): string
    {
        $str = is_scalar($value) ? trim((string) $value) : '';
        if ($str !== '' && in_array($str[0], ['=', '+', '-', '@'], true)) {
            return "'".$str;
        }

        return $str;
    }

    /**
     * Ekspor data pengguna ke format binary spreadsheet (.xlsx).
     *
     * @param  Builder<User>|Collection<int, User>  $query
     */
    public function exportUsers(Builder|Collection $query): string
    {
        $users = $query instanceof Builder
            ? $query->with(['classes', 'children'])->orderBy('name')->get()
            : $query->loadMissing(['classes', 'children']);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pengguna');

        // Judul & Metadata
        $sheet->setCellValue('A1', 'REKAPITULASI DATA PENGGUNA AKSARA');
        $sheet->setCellValue('A2', 'Waktu Unduh: '.now()->translatedFormat('d F Y H:i').' WIB | Total: '.$users->count().' pengguna');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F766E');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // Header Tabel
        $headers = ['No', 'Nama Lengkap', 'Email', 'Peran (Role)', 'Kelas / Rombel', 'Siswa Binaan', 'Status Verifikasi', 'Tanggal Dibuat'];
        $sheet->fromArray($headers, null, 'A4');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A4:H4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(26);

        $row = 5;
        $no = 1;
        foreach ($users as $user) {
            $roleLabel = $user->role ? $user->role->label() : ($user->getRoleNames()->first() ?? '—');
            $classList = $user->classes->pluck('name')->join(', ');
            $childrenList = $user->children->pluck('name')->join(', ');
            $isVerified = $user->email_verified_at ? 'Terverifikasi' : 'Belum Verifikasi';
            $createdAt = $user->created_at?->format('d/m/Y H:i') ?? '—';

            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $this->sanitizeCell($user->name));
            $sheet->setCellValue("C{$row}", $this->sanitizeCell($user->email));
            $sheet->setCellValue("D{$row}", $roleLabel);
            $sheet->setCellValue("E{$row}", $classList !== '' ? $classList : '—');
            $sheet->setCellValue("F{$row}", $childrenList !== '' ? $childrenList : '—');
            $sheet->setCellValue("G{$row}", $isVerified);
            $sheet->setCellValue("H{$row}", $createdAt);

            if ($no % 2 === 0) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $row++;
            $no++;
        }

        $lastRow = max(5, $row - 1);
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ];
        $sheet->getStyle("A4:H{$lastRow}")->applyFromArray($borderStyle);

        // Auto column dimensions
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->writeSpreadsheetToString($spreadsheet);
    }

    /**
     * Menghasilkan template resmi (.xlsx) untuk impor pengguna.
     */
    public function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet;

        // Sheet 1: Template Utama
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Form Impor Pengguna');

        $headers = ['Nama Lengkap', 'Email', 'Peran', 'Kelas / Rombel', 'Password (Opsional)'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // Contoh Data
        $sampleData = [
            ['Budi Raharjo', 'budi.raharjo@sekolah.test', 'Guru', '', ''],
            ['Siti Aminah', 'siti.aminah@siswa.test', 'Siswa', 'VII-A', ''],
            ['Ahmad Fauzi', 'ahmad.fauzi@sekolah.test', 'Wali Kelas', 'VII-A', ''],
            ['Ratna Dewi', 'ratna.dewi@wali.test', 'Wali Murid', '', ''],
        ];
        $sheet->fromArray($sampleData, null, 'A2');

        // Keterangan petunjuk di bawah contoh
        $sheet->setCellValue('A7', 'PETUNJUK PENGISIAN:');
        $sheet->setCellValue('A8', '1. Kolom "Peran" wajib diisi salah satu: Admin, Guru, Wali Kelas, Siswa, Wali Murid.');
        $sheet->setCellValue('A9', '2. Kolom "Kelas / Rombel" diisi nama rombel siswa (contoh: VII-A). Lihat tab "Referensi Rombel".');
        $sheet->setCellValue('A10', '3. Kolom "Password (Opsional)" boleh dikosongkan untuk menggunakan opsi password di sistem.');
        $sheet->getStyle('A7')->getFont()->setBold(true)->getColor()->setRGB('0F766E');
        $sheet->getStyle('A8:A10')->getFont()->setSize(9)->getColor()->setRGB('475569');

        // Data Validation Dropdown pada kolom Peran (C2:C500)
        $validation = $sheet->getCell('C2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Pilihan Tidak Valid');
        $validation->setError('Pilih peran dari daftar: Admin, Guru, Wali Kelas, Siswa, Wali Murid');
        $validation->setPromptTitle('Pilih Peran');
        $validation->setPrompt('Pilih peran pengguna dari menu dropdown.');
        $validation->setFormula1('"Admin,Guru,Wali Kelas,Siswa,Wali Murid"');

        for ($r = 2; $r <= 200; $r++) {
            $sheet->setDataValidation("C{$r}", clone $validation);
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Sheet 2: Referensi Rombel
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Referensi Rombel');

        $refHeaders = ['ID Rombel', 'Nama Rombel', 'Tingkat (Grade)', 'Tahun Ajaran'];
        $refSheet->fromArray($refHeaders, null, 'A1');
        $refSheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '334155'],
            ],
        ]);

        $rombels = SchoolClass::query()->with('academicYear')->orderBy('grade')->orderBy('name')->get();
        $refRow = 2;
        foreach ($rombels as $rombel) {
            $refSheet->setCellValue("A{$refRow}", $rombel->id);
            $refSheet->setCellValue("B{$refRow}", $rombel->name);
            $refSheet->setCellValue("C{$refRow}", 'Kelas '.$rombel->grade);
            $yearName = $rombel->academicYear ? $rombel->academicYear->name : '—';
            $refSheet->setCellValue("D{$refRow}", $yearName);
            $refRow++;
        }

        foreach (range('A', 'D') as $col) {
            $refSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Aktifkan sheet pertama
        $spreadsheet->setActiveSheetIndex(0);

        return $this->writeSpreadsheetToString($spreadsheet);
    }

    /**
     * Memproses impor data pengguna dari file spreadsheet (.xlsx/.xls).
     *
     * @param  array{duplicate_mode?: string, password_mode?: string, default_password?: string}  $options
     * @return array{imported: int, updated: int, skipped: int, errors: list<string>, credentials: list<array{name: string, email: string, role: string, password: string}>}
     */
    public function importUsers(UploadedFile|string $file, array $options = []): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        $spreadsheet = SpreadsheetIOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $duplicateMode = $options['duplicate_mode'] ?? 'skip'; // 'skip' | 'update'
        $passwordMode = $options['password_mode'] ?? 'default'; // 'default' | 'random'
        $defaultPassword = trim($options['default_password'] ?? 'Aksara2026!');
        if ($defaultPassword === '') {
            $defaultPassword = 'Aksara2026!';
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $credentials = [];

        if (count($rows) < 2) {
            return [
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['File spreadsheet kosong atau tidak memiliki data baris pengguna.'],
                'credentials' => [],
            ];
        }

        // Cache rombel untuk lookup cepat: nama huruf kecil -> id
        $classMap = [];
        foreach (SchoolClass::query()->get(['id', 'name', 'rombel_code']) as $sc) {
            $classMap[strtolower(trim($sc->name))] = $sc->id;
            if ($sc->rombel_code) {
                $classMap[strtolower(trim($sc->rombel_code))] = $sc->id;
            }
        }

        // Pastikan semua role Spatie tersedia
        foreach (UserRole::cases() as $r) {
            Role::firstOrCreate(['name' => $r->value, 'guard_name' => 'web']);
        }

        // Deteksi indeks kolom berdasarkan nama header (baris 0)
        $headerRow = $rows[0];
        $nameCol = null;
        $emailCol = null;
        $roleCol = null;
        $classCol = null;
        $passwordCol = null;

        foreach ($headerRow as $idx => $val) {
            if (! is_scalar($val)) {
                continue;
            }
            $cleanH = strtolower(trim((string) $val));
            if (str_contains($cleanH, 'rombel') || str_contains($cleanH, 'kelas') || str_contains($cleanH, 'class')) {
                $classCol = $idx;
            } elseif (str_contains($cleanH, 'email') || str_contains($cleanH, 'surel')) {
                $emailCol = $idx;
            } elseif (str_contains($cleanH, 'peran') || str_contains($cleanH, 'role')) {
                $roleCol = $idx;
            } elseif (str_contains($cleanH, 'password') || str_contains($cleanH, 'sandi')) {
                $passwordCol = $idx;
            } elseif (str_contains($cleanH, 'nama')) {
                $nameCol = $idx;
            }
        }

        // Positional fallback jika header kustom tanpa teks standar
        $nameCol ??= 0;
        $emailCol ??= 1;
        $roleCol ??= 2;
        $classCol ??= 3;
        if ($passwordCol === null && count($headerRow) > 4 && $classCol !== 4) {
            $passwordCol = 4;
        }

        $currentUserId = Auth::id();

        DB::transaction(function () use (
            $rows,
            $nameCol,
            $emailCol,
            $roleCol,
            $classCol,
            $passwordCol,
            $duplicateMode,
            $passwordMode,
            $defaultPassword,
            $classMap,
            $currentUserId,
            &$imported,
            &$updated,
            &$skipped,
            &$errors,
            &$credentials
        ) {
            // Lewati baris 1 (Header)
            for ($i = 1; $i < count($rows); $i++) {
                $lineNum = $i + 1;
                $row = $rows[$i];

                $name = isset($row[$nameCol]) && is_scalar($row[$nameCol]) ? trim((string) $row[$nameCol]) : '';
                $email = isset($row[$emailCol]) && is_scalar($row[$emailCol]) ? trim((string) $row[$emailCol]) : '';
                $rawRole = isset($row[$roleCol]) && is_scalar($row[$roleCol]) ? trim((string) $row[$roleCol]) : '';
                $className = isset($row[$classCol]) && is_scalar($row[$classCol]) ? trim((string) $row[$classCol]) : '';
                $rowPassword = $passwordCol !== null && isset($row[$passwordCol]) && is_scalar($row[$passwordCol]) ? trim((string) $row[$passwordCol]) : '';

                // Abaikan baris kosong total atau baris petunjuk
                if ($name === '' && $email === '') {
                    continue;
                }
                if (str_starts_with(strtoupper($name), 'PETUNJUK')) {
                    break;
                }

                // Validasi data baris wajib
                if ($name === '') {
                    $errors[] = "Baris {$lineNum}: Nama lengkap tidak boleh kosong.";

                    continue;
                }
                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Baris {$lineNum}: Format email '{$email}' tidak valid.";

                    continue;
                }

                // Resolusi Role
                $resolvedRole = $this->resolveRole($rawRole);
                if (! $resolvedRole) {
                    $errors[] = "Baris {$lineNum}: Peran '{$rawRole}' tidak dikenali. Gunakan: Admin, Guru, Wali Kelas, Siswa, atau Wali Murid.";

                    continue;
                }

                // Resolusi Kelas/Rombel
                $classId = null;
                if ($className !== '') {
                    $lookupKey = strtolower($className);
                    if (isset($classMap[$lookupKey])) {
                        $classId = $classMap[$lookupKey];
                    } else {
                        $errors[] = "Baris {$lineNum}: Rombel '{$className}' tidak ditemukan pada sistem.";

                        continue;
                    }
                }

                // Cek eksistensi pengguna
                $existing = User::query()->where('email', $email)->first();

                if ($existing) {
                    if ($duplicateMode === 'skip') {
                        $skipped++;

                        continue;
                    }

                    // Mode update
                    if ($existing->id === $currentUserId) {
                        // Jangan ubah akun admin yang sedang login via impor
                        $skipped++;

                        continue;
                    }

                    $existing->name = $name;
                    $existing->role = $resolvedRole;
                    $existing->save();
                    $existing->syncAppRole();

                    if ($classId && $resolvedRole === UserRole::Student) {
                        $existing->classes()->syncWithoutDetaching([$classId]);
                    }

                    $updated++;

                    continue;
                }

                // Tentukan Password Akun Baru
                $isRandom = in_array($passwordMode, ['generate', 'random'], true);
                $plainPassword = $rowPassword !== ''
                    ? $rowPassword
                    : ($isRandom ? Str::random(10) : $defaultPassword);

                $newUser = User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($plainPassword),
                    'role' => $resolvedRole,
                    'email_verified_at' => now(),
                ]);

                $newUser->syncAppRole();

                if ($classId && $resolvedRole === UserRole::Student) {
                    $newUser->classes()->syncWithoutDetaching([$classId]);
                }

                $credentials[] = [
                    'name' => $name,
                    'email' => $email,
                    'role' => $resolvedRole->label(),
                    'password' => $plainPassword,
                ];

                $imported++;
            }
        });

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'credentials' => $credentials,
        ];
    }

    /**
     * Mengubah teks peran pengguna dari spreadsheet menjadi enum UserRole.
     */
    public function resolveRole(string $rawRole): ?UserRole
    {
        $normalized = strtolower(trim($rawRole));

        return match ($normalized) {
            'admin', 'administrator' => UserRole::Admin,
            'guru', 'teacher' => UserRole::Teacher,
            'wali kelas', 'wali_kelas', 'homeroom_teacher', 'homeroom teacher' => UserRole::HomeroomTeacher,
            'siswa', 'student', 'murid' => UserRole::Student,
            'wali murid', 'wali_murid', 'parent', 'orang tua', 'ortu' => UserRole::Parent,
            default => null,
        };
    }

    /**
     * Membuat file spreadsheet kredensial akun hasil generate acak pasca-impor.
     *
     * @param  list<array{name: string, email: string, role: string, password: string}>  $credentials
     */
    public function exportCredentialsExcel(array $credentials): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kredensial Akun Baru');

        $sheet->setCellValue('A1', 'REKAP KREDENSIAL AKUN HASIL IMPOR');
        $sheet->setCellValue('A2', 'Waktu Dibuat: '.now()->translatedFormat('d F Y H:i').' | Simpan file ini di tempat aman!');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('0F766E');
        $sheet->getStyle('A2')->getFont()->setSize(9)->getColor()->setRGB('E11D48');

        $headers = ['No', 'Nama Lengkap', 'Email Login', 'Peran', 'Password Sementara'];
        $sheet->fromArray($headers, null, 'A4');
        $sheet->getStyle('A4:E4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'],
            ],
        ]);

        $row = 5;
        $no = 1;
        foreach ($credentials as $cred) {
            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $this->sanitizeCell($cred['name']));
            $sheet->setCellValue("C{$row}", $this->sanitizeCell($cred['email']));
            $sheet->setCellValue("D{$row}", $cred['role']);
            $sheet->setCellValue("E{$row}", $cred['password']);
            $row++;
            $no++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->writeSpreadsheetToString($spreadsheet);
    }

    /**
     * Helper menulis objek Spreadsheet ke binary string XLSX secara aman.
     */
    private function writeSpreadsheetToString(Spreadsheet $spreadsheet): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tempFile === false) {
            throw new \RuntimeException('Gagal membuat file sementara untuk spreadsheet.');
        }

        $writer = new XlsxWriter($spreadsheet);
        $writer->save($tempFile);

        $content = file_get_contents($tempFile) ?: '';
        @unlink($tempFile);

        return $content;
    }
}
