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

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\CurriculumAtpItem;
use App\Models\CurriculumCp;
use App\Models\CurriculumTp;
use App\Models\Semester;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Studi kasus referensi: Informatika SMP Fase D kelas VII–IX (adaptasi workshop).
 * Bukan salinan resmi dokumen BSKAP — sesuaikan dengan kebijakan satuan pendidikan.
 */
class InformatikaCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::query()->firstOrCreate(
            ['code' => '2025-2026'],
            [
                'name' => '2025/2026',
                'starts_on' => '2025-07-14',
                'ends_on' => '2026-06-20',
                'is_active' => true,
            ]
        );

        AcademicYear::query()->where('id', '!=', $year->id)->update(['is_active' => false]);

        $ganjil = Semester::query()->updateOrCreate(
            ['academic_year_id' => $year->id, 'number' => 1],
            [
                'name' => 'Ganjil',
                'code' => 'ganjil',
                'starts_on' => '2025-07-14',
                'ends_on' => '2025-12-20',
                'is_active' => true,
            ]
        );

        $genap = Semester::query()->updateOrCreate(
            ['academic_year_id' => $year->id, 'number' => 2],
            [
                'name' => 'Genap',
                'code' => 'genap',
                'starts_on' => '2026-01-06',
                'ends_on' => '2026-06-20',
                'is_active' => false,
            ]
        );

        Semester::query()
            ->where('academic_year_id', $year->id)
            ->where('id', '!=', $ganjil->id)
            ->update(['is_active' => false]);

        $informatika = Subject::query()->updateOrCreate(
            ['code' => 'INF'],
            [
                'name' => 'Informatika',
                'phase' => 'D',
                'jenjang' => 'SMP',
                'description' => 'Mapel wajib Kurikulum Merdeka Fase D (kelas VII–IX). Fokus literasi digital, berpikir komputasional, dan etika warga digital.',
            ]
        );

        $elements = $this->elements();

        /** @var array<int, list<array{tp: CurriculumTp, unit: string}>> $atpByGrade */
        $atpByGrade = [7 => [], 8 => [], 9 => []];
        $seq = 1;

        foreach ($elements as $element) {
            $cp = CurriculumCp::query()->updateOrCreate(
                [
                    'subject_id' => $informatika->id,
                    'phase' => 'D',
                    'element_code' => $element['code'],
                ],
                [
                    'element_name' => $element['name'],
                    'statement' => $element['statement'],
                    'source_note' => 'Adaptasi workshop dari kerangka Kurikulum Merdeka Informatika Fase D — verifikasi ke dokumen resmi satuan pendidikan.',
                    'sequence' => $seq++,
                ]
            );

            $tpSeq = 1;
            foreach ($element['tps'] as $tpData) {
                $tp = CurriculumTp::query()->updateOrCreate(
                    [
                        'curriculum_cp_id' => $cp->id,
                        'code' => $tpData['code'],
                    ],
                    [
                        'statement' => $tpData['statement'],
                        'grade' => $tpData['grade'],
                        'sequence' => $tpSeq++,
                    ]
                );

                $grade = (int) $tpData['grade'];
                if (isset($atpByGrade[$grade])) {
                    $atpByGrade[$grade][] = [
                        'tp' => $tp,
                        'unit' => $element['name'],
                    ];
                }
            }
        }

        CurriculumAtpItem::query()
            ->where('subject_id', $informatika->id)
            ->where('academic_year_id', $year->id)
            ->whereIn('grade', [7, 8, 9])
            ->delete();

        foreach ([7, 8, 9] as $grade) {
            $rows = $atpByGrade[$grade];
            $cutoff = (int) ceil(count($rows) / 2);
            $sequence = 1;

            foreach ($rows as $i => $row) {
                CurriculumAtpItem::query()->create([
                    'subject_id' => $informatika->id,
                    'academic_year_id' => $year->id,
                    'semester_id' => $i < $cutoff ? $ganjil->id : $genap->id,
                    'curriculum_tp_id' => $row['tp']->id,
                    'grade' => $grade,
                    'sequence' => $sequence++,
                    'unit_title' => $row['unit'],
                    'estimated_meetings' => 2,
                ]);
            }
        }

        $this->command?->info('Referensi Informatika Fase D + TP/ATP kelas VII–IX tersimpan.');
    }

    /**
     * @return list<array{code: string, name: string, statement: string, tps: list<array{code: string, grade: int, statement: string}>}>
     */
    private function elements(): array
    {
        return [
            [
                'code' => 'BK',
                'name' => 'Berpikir Komputasional',
                'statement' => 'Peserta didik mampu menerapkan konsep berpikir komputasional (dekomposisi, pengenalan pola, abstraksi, dan algoritma) untuk memahami dan menyelesaikan masalah kontekstual secara sistematis.',
                'tps' => [
                    ['code' => 'BK-VII-01', 'grade' => 7, 'statement' => 'Mengidentifikasi masalah sehari-hari yang dapat dipecah menjadi bagian lebih kecil (dekomposisi).'],
                    ['code' => 'BK-VII-02', 'grade' => 7, 'statement' => 'Mengenali pola berulang dalam data atau prosedur sederhana.'],
                    ['code' => 'BK-VII-03', 'grade' => 7, 'statement' => 'Menyusun langkah solusi masalah secara runtut dalam bentuk algoritma sederhana (teks/pseudocode).'],
                    ['code' => 'BK-VIII-01', 'grade' => 8, 'statement' => 'Menerapkan dekomposisi dan abstraksi pada masalah yang lebih kompleks di lingkungan sekolah.'],
                    ['code' => 'BK-VIII-02', 'grade' => 8, 'statement' => 'Membandingkan beberapa strategi solusi berdasarkan pola dan efisiensi langkah.'],
                    ['code' => 'BK-IX-01', 'grade' => 9, 'statement' => 'Merancang solusi komputasional terintegrasi untuk masalah lintas mapel dengan tahapan BK lengkap.'],
                    ['code' => 'BK-IX-02', 'grade' => 9, 'statement' => 'Mengevaluasi dan merevisi algoritma solusi berdasarkan uji coba dan umpan balik.'],
                ],
            ],
            [
                'code' => 'TIK',
                'name' => 'Teknologi Informasi dan Komunikasi',
                'statement' => 'Peserta didik mampu memanfaatkan perkakas TIK secara produktif untuk mencari, mengelola, membuat, dan menyajikan informasi secara beretika.',
                'tps' => [
                    ['code' => 'TIK-VII-01', 'grade' => 7, 'statement' => 'Menggunakan aplikasi perkantoran untuk membuat dokumen dan presentasi sederhana.'],
                    ['code' => 'TIK-VII-02', 'grade' => 7, 'statement' => 'Mencari dan mengevaluasi kredibilitas informasi digital dasar.'],
                    ['code' => 'TIK-VIII-01', 'grade' => 8, 'statement' => 'Mengelola berkas dan kolaborasi daring (folder bersama, versi dokumen) secara tertib.'],
                    ['code' => 'TIK-VIII-02', 'grade' => 8, 'statement' => 'Membuat produk digital multimedia sederhana (gambar/audio/video pendek) untuk presentasi.'],
                    ['code' => 'TIK-IX-01', 'grade' => 9, 'statement' => 'Merancang dan memublikasikan konten digital sesuai tujuan komunikasi dan audiens.'],
                    ['code' => 'TIK-IX-02', 'grade' => 9, 'statement' => 'Menerapkan praktik sitasi dan lisensi dasar saat menggunakan sumber digital.'],
                ],
            ],
            [
                'code' => 'SK',
                'name' => 'Sistem Komputer',
                'statement' => 'Peserta didik mampu menjelaskan komponen, fungsi, dan cara kerja dasar sistem komputer serta membedakan jenis perangkat keras dan perangkat lunak.',
                'tps' => [
                    ['code' => 'SK-VII-01', 'grade' => 7, 'statement' => 'Menjelaskan fungsi perangkat keras input, proses, output, dan penyimpanan.'],
                    ['code' => 'SK-VII-02', 'grade' => 7, 'statement' => 'Membedakan sistem operasi, aplikasi, dan utilitas beserta contohnya.'],
                    ['code' => 'SK-VIII-01', 'grade' => 8, 'statement' => 'Menjelaskan alur data dari input hingga output pada sistem komputer sederhana.'],
                    ['code' => 'SK-VIII-02', 'grade' => 8, 'statement' => 'Mendiagnosis gangguan umum (tidak nyala, lambat, penyimpanan penuh) secara dasar.'],
                    ['code' => 'SK-IX-01', 'grade' => 9, 'statement' => 'Membandingkan pilihan spesifikasi perangkat sesuai kebutuhan pengguna.'],
                    ['code' => 'SK-IX-02', 'grade' => 9, 'statement' => 'Menjelaskan peran firmware/driver dan pembaruan sistem untuk keamanan dasar.'],
                ],
            ],
            [
                'code' => 'JKI',
                'name' => 'Jaringan Komputer dan Internet',
                'statement' => 'Peserta didik memahami konsep dasar jaringan dan internet serta praktik keamanan perangkat yang terhubung.',
                'tps' => [
                    ['code' => 'JKI-VII-01', 'grade' => 7, 'statement' => 'Menjelaskan perbedaan jaringan lokal dan internet secara sederhana.'],
                    ['code' => 'JKI-VII-02', 'grade' => 7, 'statement' => 'Menerapkan kebiasaan aman saat terhubung ke jaringan (kata sandi, izin aplikasi).'],
                    ['code' => 'JKI-VIII-01', 'grade' => 8, 'statement' => 'Menjelaskan peran perangkat jaringan dasar (router, switch, access point) secara sederhana.'],
                    ['code' => 'JKI-VIII-02', 'grade' => 8, 'statement' => 'Mengenali risiko phishing dan praktik verifikasi tautan/pesan.'],
                    ['code' => 'JKI-IX-01', 'grade' => 9, 'statement' => 'Merancang topologi jaringan sederhana untuk kebutuhan kelas/lab.'],
                    ['code' => 'JKI-IX-02', 'grade' => 9, 'statement' => 'Menerapkan pengaturan privasi dan keamanan akun daring secara konsisten.'],
                ],
            ],
            [
                'code' => 'AD',
                'name' => 'Analisis Data',
                'statement' => 'Peserta didik mampu mengakses, mengolah, dan menafsirkan data berukuran kecil untuk menarik kesimpulan sederhana.',
                'tps' => [
                    ['code' => 'AD-VII-01', 'grade' => 7, 'statement' => 'Mengumpulkan dan menata data sederhana dalam tabel.'],
                    ['code' => 'AD-VII-02', 'grade' => 7, 'statement' => 'Membaca grafik/tabel dan menarik kesimpulan dasar.'],
                    ['code' => 'AD-VIII-01', 'grade' => 8, 'statement' => 'Membersihkan data sederhana (duplikat, nilai kosong) sebelum dianalisis.'],
                    ['code' => 'AD-VIII-02', 'grade' => 8, 'statement' => 'Membuat visualisasi data (diagram batang/garis/lingkaran) dari data sekolah.'],
                    ['code' => 'AD-IX-01', 'grade' => 9, 'statement' => 'Menafsirkan tren data dan menyusun rekomendasi berbasis bukti.'],
                    ['code' => 'AD-IX-02', 'grade' => 9, 'statement' => 'Mengidentifikasi bias/batasan data pada kesimpulan yang dibuat.'],
                ],
            ],
            [
                'code' => 'AP',
                'name' => 'Algoritma dan Pemrograman',
                'statement' => 'Peserta didik mampu merancang algoritma dan mengimplementasikannya dalam lingkungan pemrograman visual (blok) untuk solusi sederhana.',
                'tps' => [
                    ['code' => 'AP-VII-01', 'grade' => 7, 'statement' => 'Membuat program visual blok dengan urutan, perulangan, atau percabangan sederhana.'],
                    ['code' => 'AP-VII-02', 'grade' => 7, 'statement' => 'Menguji dan memperbaiki kesalahan program sederhana (debugging dasar).'],
                    ['code' => 'AP-VIII-01', 'grade' => 8, 'statement' => 'Menggunakan variabel dan struktur kontrol untuk menyelesaikan masalah terstruktur.'],
                    ['code' => 'AP-VIII-02', 'grade' => 8, 'statement' => 'Memecah program menjadi bagian/fungsi kecil yang dapat diuji ulang.'],
                    ['code' => 'AP-IX-01', 'grade' => 9, 'statement' => 'Mengembangkan proyek pemrograman mini dengan perencanaan, implementasi, dan pengujian.'],
                    ['code' => 'AP-IX-02', 'grade' => 9, 'statement' => 'Mendokumentasikan kode/algoritma agar dapat dipahami orang lain.'],
                ],
            ],
            [
                'code' => 'DSI',
                'name' => 'Dampak Sosial Informatika',
                'statement' => 'Peserta didik mampu bersikap sebagai warga digital yang beretika, menghargai privasi, dan menyadari dampak sosial teknologi.',
                'tps' => [
                    ['code' => 'DSI-VII-01', 'grade' => 7, 'statement' => 'Menjelaskan contoh perilaku positif dan negatif di ruang digital.'],
                    ['code' => 'DSI-VII-02', 'grade' => 7, 'statement' => 'Menerapkan etika komunikasi digital dalam konteks sekolah.'],
                    ['code' => 'DSI-VIII-01', 'grade' => 8, 'statement' => 'Menganalisis isu privasi data pribadi pada layanan digital yang dipakai siswa.'],
                    ['code' => 'DSI-VIII-02', 'grade' => 8, 'statement' => 'Menyusun kampanye kelas tentang literasi digital yang aman dan inklusif.'],
                    ['code' => 'DSI-IX-01', 'grade' => 9, 'statement' => 'Mengevaluasi dampak sosial AI/teknologi terhadap pekerjaan dan masyarakat secara sederhana.'],
                    ['code' => 'DSI-IX-02', 'grade' => 9, 'statement' => 'Merumuskan komitmen pribadi sebagai warga digital yang bertanggung jawab.'],
                ],
            ],
            [
                'code' => 'PLB',
                'name' => 'Praktik Lintas Bidang',
                'statement' => 'Peserta didik mampu berkolaborasi menghasilkan artefak komputasional sederhana dengan menerapkan proses rekayasa/pengembangan secara kreatif.',
                'tps' => [
                    ['code' => 'PLB-VII-01', 'grade' => 7, 'statement' => 'Merancang dan mempresentasikan artefak digital sederhana secara berkelompok.'],
                    ['code' => 'PLB-VIII-01', 'grade' => 8, 'statement' => 'Menjalankan siklus rancang–buat–uji untuk produk digital lintas mapel.'],
                    ['code' => 'PLB-VIII-02', 'grade' => 8, 'statement' => 'Membagi peran tim dan mendokumentasikan proses kolaborasi proyek.'],
                    ['code' => 'PLB-IX-01', 'grade' => 9, 'statement' => 'Menghasilkan proyek lintas bidang yang menyelesaikan masalah nyata sekolah/komunitas.'],
                    ['code' => 'PLB-IX-02', 'grade' => 9, 'statement' => 'Merefleksikan proses proyek dan merencanakan perbaikan iterasi berikutnya.'],
                ],
            ],
        ];
    }
}
