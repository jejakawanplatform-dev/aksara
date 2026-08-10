<?php

namespace Database\Seeders;

use App\Enums\MaterialStatus;
use App\Enums\PlanStatus;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\CurriculumCp;
use App\Models\CurriculumTp;
use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Models\Quiz;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AiProviderSeeder::class);
        $this->call([
            RolePermissionSeeder::class,
            InformatikaCurriculumSeeder::class,
            SystemSettingSeeder::class,
        ]);

        $year = AcademicYear::active() ?? AcademicYear::query()->firstOrFail();
        $semester = Semester::active()
            ?? Semester::query()->where('academic_year_id', $year->id)->where('number', 1)->firstOrFail();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@aksara.test'],
            [
                'name' => 'Admin Aksara',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );
        $admin->syncAppRole();

        $guru = User::query()->updateOrCreate(
            ['email' => 'naya@aksara.test'],
            [
                'name' => 'Ibu Naya',
                'password' => Hash::make('password'),
                'role' => UserRole::Teacher,
                'email_verified_at' => now(),
            ],
        );
        $guru->syncAppRole();

        $waliKelas = User::query()->updateOrCreate(
            ['email' => 'arif@aksara.test'],
            [
                'name' => 'Pak Arif',
                'password' => Hash::make('password'),
                'role' => UserRole::HomeroomTeacher,
                'email_verified_at' => now(),
            ],
        );
        $waliKelas->syncAppRole();

        $namasSiswa = ['Adit', 'Bunga', 'Citra', 'Dimas', 'Eka'];
        $siswaList = [];
        foreach ($namasSiswa as $nama) {
            $s = User::query()->updateOrCreate(
                ['email' => strtolower($nama).'@aksara.test'],
                [
                    'name' => $nama,
                    'password' => Hash::make('password'),
                    'role' => UserRole::Student,
                    'email_verified_at' => now(),
                ],
            );
            $s->syncAppRole();
            $siswaList[] = $s;
        }

        $waliAdit = User::query()->updateOrCreate(
            ['email' => 'ortu.adit@aksara.test'],
            [
                'name' => 'Ortu Adit',
                'password' => Hash::make('password'),
                'role' => UserRole::Parent,
                'email_verified_at' => now(),
            ],
        );
        $waliAdit->syncAppRole();

        $waliBunga = User::query()->updateOrCreate(
            ['email' => 'ortu.bunga@aksara.test'],
            [
                'name' => 'Ortu Bunga',
                'password' => Hash::make('password'),
                'role' => UserRole::Parent,
                'email_verified_at' => now(),
            ],
        );
        $waliBunga->syncAppRole();

        $kelas = SchoolClass::query()->updateOrCreate(
            [
                'academic_year_id' => $year->id,
                'rombel_code' => 'VII-A',
            ],
            [
                'name' => 'VII-A',
                'grade' => 7,
                'homeroom_teacher_id' => $waliKelas->id,
            ],
        );
        $kelas->students()->sync(collect($siswaList)->pluck('id'));

        SchoolClass::query()->updateOrCreate(
            [
                'academic_year_id' => $year->id,
                'rombel_code' => 'VIII-A',
            ],
            [
                'name' => 'VIII-A',
                'grade' => 8,
                'homeroom_teacher_id' => null,
            ],
        );

        SchoolClass::query()->updateOrCreate(
            [
                'academic_year_id' => $year->id,
                'rombel_code' => 'IX-A',
            ],
            [
                'name' => 'IX-A',
                'grade' => 9,
                'homeroom_teacher_id' => null,
            ],
        );

        DB::table('parent_students')->upsert(
            [
                ['parent_id' => $waliAdit->id, 'student_id' => $siswaList[0]->id],
                ['parent_id' => $waliBunga->id, 'student_id' => $siswaList[1]->id],
            ],
            ['parent_id', 'student_id'],
        );

        Subject::query()->firstOrCreate(['name' => 'Matematika'], ['code' => 'MTK', 'phase' => 'D', 'jenjang' => 'SMP']);
        Subject::query()->firstOrCreate(['name' => 'IPA'], ['code' => 'IPA', 'phase' => 'D', 'jenjang' => 'SMP']);
        Subject::query()->firstOrCreate(['name' => 'Bahasa Indonesia'], ['code' => 'BIN', 'phase' => 'D', 'jenjang' => 'SMP']);

        $informatika = Subject::query()->where('code', 'INF')->firstOrFail();
        $informatika->teachers()->syncWithoutDetaching([$guru->id]);

        $cpBk = CurriculumCp::query()
            ->where('subject_id', $informatika->id)
            ->where('element_code', 'BK')
            ->firstOrFail();
        $tpBk = CurriculumTp::query()
            ->where('curriculum_cp_id', $cpBk->id)
            ->where('code', 'BK-VII-01')
            ->firstOrFail();

        $plan = LearningPlan::query()->firstOrCreate(
            [
                'teacher_id' => $guru->id,
                'topic' => 'Berpikir Komputasional: Dekomposisi Masalah',
                'class_id' => $kelas->id,
            ],
            [
                'academic_year_id' => $year->id,
                'semester_id' => $semester->id,
                'subject_id' => $informatika->id,
                'curriculum_cp_id' => $cpBk->id,
                'curriculum_tp_id' => $tpBk->id,
                'phase' => 'D',
                'grade' => 7,
                'duration_minutes' => 80,
                'learning_objectives' => 'Peserta didik mampu memecah masalah sehari-hari menjadi bagian kecil dan menyusun langkah solusi sederhana.',
                'student_needs' => 'Contoh kontekstual sekolah; kerja berpasangan',
                'curriculum_reference' => $cpBk->label().' — TP '.$tpBk->code.' — '.$tpBk->statement,
                'status' => PlanStatus::Published,
            ],
        );

        LearningMaterial::query()->firstOrCreate(
            ['plan_id' => $plan->id],
            [
                'content' => [
                    'title' => 'Materi: Dekomposisi Masalah',
                    'sections' => [
                        ['heading' => 'Apa itu berpikir komputasional?', 'body' => 'Cara memecahkan masalah dengan pola berpikir yang dipakai dalam ilmu komputer: dekomposisi, pola, abstraksi, dan algoritma.'],
                        ['heading' => 'Dekomposisi', 'body' => 'Memecah masalah besar menjadi bagian lebih kecil agar lebih mudah diselesaikan, misalnya merencanakan acara kelas.'],
                        ['heading' => 'Latihan', 'body' => 'Pilih satu masalah sekolah, pecah menjadi 4–6 langkah, lalu diskusikan dengan teman.'],
                    ],
                    'reflectionQuestion' => 'Masalah apa di sekolahmu yang paling cocok dipecah dengan dekomposisi?',
                ],
                'status' => MaterialStatus::Published,
                'published_at' => now(),
            ],
        );

        Quiz::query()->firstOrCreate(
            [
                'plan_id' => $plan->id,
                'title' => 'Kuis: Dekomposisi Masalah',
            ],
            [
                'status' => 'published',
                'questions' => [
                    [
                        'question' => 'Dekomposisi berarti…',
                        'options' => ['Memecah masalah jadi bagian lebih kecil', 'Menghapus data', 'Menginstal aplikasi', 'Membuat kata sandi'],
                        'correct_answer' => 'Memecah masalah jadi bagian lebih kecil',
                    ],
                    [
                        'question' => 'Manakah contoh berpikir komputasional?',
                        'options' => ['Menyusun langkah memasak mie instan', 'Tidur siang', 'Menebak tanpa data', 'Mengabaikan error'],
                        'correct_answer' => 'Menyusun langkah memasak mie instan',
                    ],
                    [
                        'question' => 'Elemen CP Informatika untuk topik ini adalah…',
                        'options' => ['Berpikir Komputasional', 'Olahraga', 'Seni rupa', 'Bahasa daerah'],
                        'correct_answer' => 'Berpikir Komputasional',
                    ],
                ],
            ],
        );
        $this->command->newLine();
        $this->command->info('Demo siap: Tahun ajaran '.$year->name.', semester '.$semester->name.', rombel VII-A/VIII-A/IX-A, mapel Informatika, CP BK + materi/kuis.');
        $this->command->table(
            ['Role', 'Nama', 'Email', 'Password'],
            [
                ['Admin', 'Admin Aksara', 'admin@aksara.test', 'password'],
                ['Guru', 'Ibu Naya', 'naya@aksara.test', 'password'],
                ['Wali Kelas', 'Pak Arif', 'arif@aksara.test', 'password'],
                ['Siswa', 'Adit', 'adit@aksara.test', 'password'],
                ['Wali Murid', 'Ortu Adit', 'ortu.adit@aksara.test', 'password'],
            ]
        );
    }
}
