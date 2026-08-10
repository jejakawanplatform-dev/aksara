<?php

namespace Database\Seeders;

use App\Services\SettingService;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        /** @var SettingService $service */
        $service = app(SettingService::class);

        $defaults = [
            // Identitas & Profil Sekolah
            ['key' => 'school.name', 'value' => 'SMP Negeri 1 Aksara', 'type' => 'string', 'group' => 'school', 'label' => 'Nama Sekolah / Instansi'],
            ['key' => 'school.npsn', 'value' => '12345678', 'type' => 'string', 'group' => 'school', 'label' => 'NPSN'],
            ['key' => 'school.address', 'value' => 'Jl. Pendidikan No. 1, Jakarta', 'type' => 'string', 'group' => 'school', 'label' => 'Alamat Sekolah'],
            ['key' => 'school.headmaster', 'value' => 'Drs. H. Mulyadi, M.Pd.', 'type' => 'string', 'group' => 'school', 'label' => 'Nama Kepala Sekolah'],
            ['key' => 'school.phone', 'value' => '021-5551234', 'type' => 'string', 'group' => 'school', 'label' => 'No. Telepon Sekolah'],

            // Operasional Akademik
            ['key' => 'academic.passing_score', 'value' => 70, 'type' => 'integer', 'group' => 'academic', 'label' => 'Nilai Kelulusan KKM Kuis'],
            ['key' => 'academic.quiz_attempt_limit', 'value' => 1, 'type' => 'integer', 'group' => 'academic', 'label' => 'Batas Percobaan Kuis'],
            ['key' => 'academic.attendance_tolerance_minutes', 'value' => 15, 'type' => 'integer', 'group' => 'academic', 'label' => 'Toleransi Menit Keterlambatan'],

            // Layanan & Guard AI

            ['key' => 'ai.provider', 'value' => 'gemini', 'type' => 'string', 'group' => 'ai', 'label' => 'Provider AI Utama'],
            ['key' => 'ai.default_model', 'value' => 'gemini-1.5-flash', 'type' => 'string', 'group' => 'ai', 'label' => 'Model LLM AI'],
            ['key' => 'ai.model_plan', 'value' => 'llama-3.3-70b-versatile', 'type' => 'string', 'group' => 'ai', 'label' => 'Model Rekomendasi: Rencana Pembelajaran'],
            ['key' => 'ai.model_material', 'value' => 'llama-3.3-70b-versatile', 'type' => 'string', 'group' => 'ai', 'label' => 'Model Rekomendasi: Bahan Ajar / Co-Pilot'],
            ['key' => 'ai.model_improve', 'value' => 'llama-3.1-8b-instant', 'type' => 'string', 'group' => 'ai', 'label' => 'Model Rekomendasi: Perbaiki Teks'],
            ['key' => 'ai.model_quiz', 'value' => 'llama-3.3-70b-versatile', 'type' => 'string', 'group' => 'ai', 'label' => 'Model Rekomendasi: Soal / Kuis'],
            ['key' => 'ai.daily_limit_per_teacher', 'value' => 20, 'type' => 'integer', 'group' => 'ai', 'label' => 'Batas Generasi AI per Guru / Hari'],
            ['key' => 'ai.anonymize_student_data', 'value' => true, 'type' => 'boolean', 'group' => 'ai', 'label' => 'Penegakan Anonimisasi Data Siswa'],

            // Keamanan & Akses
            ['key' => 'security.allow_public_registration', 'value' => false, 'type' => 'boolean', 'group' => 'security', 'label' => 'Pendaftaran Publik Siswa / Ortu'],
            ['key' => 'security.session_timeout_minutes', 'value' => 60, 'type' => 'integer', 'group' => 'security', 'label' => 'Durasi Sesi Inaktif (Menit)'],
            ['key' => 'security.max_login_attempts', 'value' => 5, 'type' => 'integer', 'group' => 'security', 'label' => 'Batas Percobaan Login Gagal'],

            // Feature Flags & Pemeliharaan
            ['key' => 'features.quiz_module', 'value' => true, 'type' => 'boolean', 'group' => 'features', 'label' => 'Modul Kuis Online'],
            ['key' => 'features.parent_portal', 'value' => true, 'type' => 'boolean', 'group' => 'features', 'label' => 'Portal Akses Wali Murid'],
            ['key' => 'system.maintenance_mode', 'value' => false, 'type' => 'boolean', 'group' => 'features', 'label' => 'Mode Pemeliharaan Sistem'],
        ];

        foreach ($defaults as $item) {
            $service->set($item['key'], $item['value'], $item['type'], $item['group'], $item['label']);
        }
    }
}
