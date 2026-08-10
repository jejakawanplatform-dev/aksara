<?php

namespace App\Support\Ai;

class AiVendorProviderCatalog
{
    public static function all(): array
    {
        return [
            'gemini' => [
                'id' => 'gemini',
                'name' => 'Google Gemini AI',
                'badge' => 'Gratis & Paid',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'default_model' => 'gemini-1.5-flash',
                'models' => ['gemini-1.5-flash', 'gemini-1.5-pro', 'gemini-2.0-flash-exp'],
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'requires_key' => true,
                'supports_image_generation' => true,
                'docs_url' => 'https://aistudio.google.com/app/apikey',
                'rpm' => '15 RPM (Free) / 1000 RPM (Paid)',
                'tpm' => '1,000,000 TPM',
                'rpd' => '1,500 RPD (Free)',
                'cost_per_1k_input' => 0.000075, // $0.075 / 1M
                'cost_per_1k_output' => 0.000300, // $0.30 / 1M
                'reset_policy' => 'Reset kuota harian otomatis pukul 00:00 UTC / 07:00 WIB.',
                'guide' => "1. Buka Google AI Studio (aistudio.google.com)\n2. Buat API Key baru secara gratis\n3. Salin API Key dan masukkan pada form konfigurasi.",
            ],
            'groq' => [
                'id' => 'groq',
                'name' => 'Groq Cloud (Llama & DeepSeek)',
                'badge' => 'Ultra Fast & Free',
                'badge_color' => 'bg-amber-100 text-amber-800',
                'default_model' => 'llama-3.3-70b-versatile',
                'models' => ['llama-3.3-70b-versatile', 'llama-3.1-8b-instant', 'deepseek-r1-distill-llama-70b', 'mixtral-8x7b-32768'],
                'base_url' => 'https://api.groq.com/openai/v1',
                'requires_key' => true,
                'supports_image_generation' => false,
                'docs_url' => 'https://console.groq.com/keys',
                'rpm' => '30 RPM (Free)',
                'tpm' => '14,400 TPM',
                'rpd' => '14,400 RPD (Free)',
                'cost_per_1k_input' => 0.000000, // Free Tier
                'cost_per_1k_output' => 0.000000,
                'reset_policy' => 'Reset rate-limit per menit (RPM) & per hari (RPD) otomatis.',
                'guide' => "1. Daftar akun di Groq Console (console.groq.com)\n2. Buat API Key gratis pada menu API Keys\n3. Kecepatan generasi sangat tinggi dengan performa Llama 3.3.",
            ],
            'openai' => [
                'id' => 'openai',
                'name' => 'OpenAI (ChatGPT)',
                'badge' => 'Paid API',
                'badge_color' => 'bg-emerald-100 text-emerald-800',
                'default_model' => 'gpt-4o-mini',
                'models' => ['gpt-4o-mini', 'gpt-4o', 'gpt-3.5-turbo'],
                'base_url' => 'https://api.openai.com/v1',
                'requires_key' => true,
                'supports_image_generation' => true,
                'docs_url' => 'https://platform.openai.com/api-keys',
                'rpm' => '500 RPM (Tier 1)',
                'tpm' => '200,000 TPM',
                'rpd' => 'Tak Terbatas (Saldo Billing)',
                'cost_per_1k_input' => 0.000150, // $0.15 / 1M
                'cost_per_1k_output' => 0.000600, // $0.60 / 1M
                'reset_policy' => 'Tergantung saldo billing akun OpenAI Platform.',
                'guide' => "1. Login ke OpenAI Platform (platform.openai.com)\n2. Buat API Key baru di menu API Keys\n3. Pastikan saldo billing/kredit aktif.",
            ],
            'deepseek' => [
                'id' => 'deepseek',
                'name' => 'DeepSeek AI Official',
                'badge' => 'Paid API',
                'badge_color' => 'bg-cyan-100 text-cyan-800',
                'default_model' => 'deepseek-chat',
                'models' => ['deepseek-chat', 'deepseek-reasoner'],
                'base_url' => 'https://api.deepseek.com/v1',
                'requires_key' => true,
                'supports_image_generation' => false,
                'docs_url' => 'https://platform.deepseek.com/api_keys',
                'rpm' => '60 RPM',
                'tpm' => '100,000 TPM',
                'rpd' => 'Tergantung Saldo',
                'cost_per_1k_input' => 0.000140, // $0.14 / 1M
                'cost_per_1k_output' => 0.000280, // $0.28 / 1M
                'reset_policy' => 'Reset otomatis per menit, kuota berdasarkan saldo.',
                'guide' => "1. Login ke DeepSeek Platform (platform.deepseek.com)\n2. Buat API Key baru di menu API Keys\n3. Salin API Key dan masukkan di form.",
            ],
            'anthropic' => [
                'id' => 'anthropic',
                'name' => 'Anthropic Claude',
                'badge' => 'Paid API',
                'badge_color' => 'bg-purple-100 text-purple-800',
                'default_model' => 'claude-3-5-haiku-20241022',
                'models' => ['claude-3-5-haiku-20241022', 'claude-3-5-sonnet-20241022'],
                'base_url' => 'https://api.anthropic.com/v1',
                'requires_key' => true,
                'supports_image_generation' => false,
                'docs_url' => 'https://console.anthropic.com/settings/keys',
                'rpm' => '50 RPM',
                'tpm' => '50,000 TPM',
                'rpd' => 'Tergantung Saldo',
                'cost_per_1k_input' => 0.000250, // $0.25 / 1M
                'cost_per_1k_output' => 0.001250, // $1.25 / 1M
                'reset_policy' => 'Reset per menit & kuota deposit kredit.',
                'guide' => "1. Buka Anthropic Console (console.anthropic.com)\n2. Buat API Key pada Settings > API Keys\n3. Rekomendasi model: Claude 3.5 Haiku.",
            ],
            'ollama' => [
                'id' => 'ollama',
                'name' => 'Ollama / Local LLM',
                'badge' => 'Lokal & Free',
                'badge_color' => 'bg-gray-100 text-gray-800',
                'default_model' => 'llama3.2',
                'models' => ['llama3.2', 'qwen2.5', 'mistral', 'deepseek-r1'],
                'base_url' => 'http://localhost:11434/v1',
                'requires_key' => false,
                'supports_image_generation' => false,
                'docs_url' => 'https://ollama.com',
                'rpm' => 'Tanpa Batas (Lokal)',
                'tpm' => 'Tanpa Batas',
                'rpd' => 'Tanpa Batas',
                'cost_per_1k_input' => 0.000000,
                'cost_per_1k_output' => 0.000000,
                'reset_policy' => 'Tidak ada batasan API (Server Mandiri).',
                'guide' => "1. Install Ollama di server/PC lokal (ollama.com)\n2. Jalankan server Ollama dan unduh model\n3. API Key boleh dikosongkan.",
            ],
            'custom' => [
                'id' => 'custom',
                'name' => 'Custom OpenAI-Compatible API',
                'badge' => 'Kustom Endpoint',
                'badge_color' => 'bg-indigo-100 text-indigo-800',
                'default_model' => 'custom-model',
                'models' => ['custom-model'],
                'base_url' => 'https://your-custom-ai-server.com/v1',
                'requires_key' => true,
                'supports_image_generation' => false,
                'docs_url' => '#',
                'rpm' => 'Sesuai Server Kustom',
                'tpm' => 'Sesuai Server Kustom',
                'rpd' => 'Sesuai Server Kustom',
                'cost_per_1k_input' => 0.000000,
                'cost_per_1k_output' => 0.000000,
                'reset_policy' => 'Kebijakan disesuaikan dengan infrastruktur kustom Anda.',
                'guide' => 'Gunakan untuk menghubungkan server AI mandiri (vLLM, LM Studio, LocalAI, LiteLLM) yang mendukung protokol OpenAI Chat Completion.',
            ],
            'mock' => [
                'id' => 'mock',
                'name' => 'Mock Mode (Simulasi Offline)',
                'badge' => 'Simulasi Offline',
                'badge_color' => 'bg-teal-100 text-teal-800',
                'default_model' => 'mock-model',
                'models' => ['mock-model'],
                'base_url' => '',
                'requires_key' => false,
                'supports_image_generation' => false,
                'docs_url' => '#',
                'rpm' => 'Tanpa Batas',
                'tpm' => 'Tanpa Batas',
                'rpd' => 'Tanpa Batas',
                'cost_per_1k_input' => 0.000000,
                'cost_per_1k_output' => 0.000000,
                'reset_policy' => 'Simulasi offline bawaan sistem Aksara.',
                'guide' => 'Mode simulasi otomatis offline untuk pengujian tanpa memerlukan koneksi atau kuota API.',
            ],
        ];
    }

    public static function get(string $providerId): ?array
    {
        return static::all()[$providerId] ?? null;
    }

    /**
     * Recommended system_settings keys + defaults for per-feature model preferences.
     *
     * @return array<string, array{key: string, label: string, default: string, hint: string, enabled: bool}>
     */
    public static function featureModelRecommendations(): array
    {
        return [
            'plan' => [
                'key' => 'ai.model_plan',
                'label' => 'Rencana Pembelajaran',
                'default' => 'llama-3.3-70b-versatile',
                'hint' => 'Generate CP/TP/ATP & draf modul ajar (butuh reasoning cukup kuat).',
                'enabled' => true,
            ],
            'material' => [
                'key' => 'ai.model_material',
                'label' => 'Bahan Ajar / Co-Pilot',
                'default' => 'llama-3.3-70b-versatile',
                'hint' => 'Obrolan Co-Pilot & penyusunan teks bacaan materi siswa.',
                'enabled' => true,
            ],
            'improve' => [
                'key' => 'ai.model_improve',
                'label' => 'Perbaiki Teks Singkat',
                'default' => 'llama-3.1-8b-instant',
                'hint' => 'Penyempurnaan field singkat (tujuan, dll.) — model cepat cukup.',
                'enabled' => true,
            ],
            'quiz' => [
                'key' => 'ai.model_quiz',
                'label' => 'Soal / Kuis (siap nanti)',
                'default' => 'llama-3.3-70b-versatile',
                'hint' => 'Cadangan untuk generate soal AI — belum aktif di UI guru.',
                'enabled' => false,
            ],
        ];
    }

    /**
     * Flatten unique model ids from all catalog vendors (for recommendation dropdowns).
     *
     * @return list<string>
     */
    public static function allCatalogModelIds(): array
    {
        $ids = [];
        foreach (static::all() as $meta) {
            foreach ($meta['models'] ?? [] as $model) {
                $ids[$model] = $model;
            }
        }

        return array_values($ids);
    }
}
