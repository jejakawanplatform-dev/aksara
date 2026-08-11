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

namespace App\Support\Ai;

use App\Models\AiProvider;

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
                'label' => 'Bahan Ajar / Asisten Aksara',
                'default' => 'llama-3.3-70b-versatile',
                'hint' => 'Obrolan Asisten Aksara & penyusunan teks bacaan materi siswa.',
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

    /**
     * Model IDs available from the given AI provider rows (active vendor context).
     *
     * @param  iterable<AiProvider>  $providers
     * @return list<string>
     */
    public static function modelIdsFromProviders(iterable $providers): array
    {
        $ids = [];

        foreach ($providers as $provider) {
            $vendorMeta = $provider->catalogMeta() ?? static::get($provider->vendor_key) ?? [];
            $models = $vendorMeta['models'] ?? [];

            if ($provider->is_custom || $models === []) {
                $fallback = trim((string) ($provider->model ?: ($vendorMeta['default_model'] ?? '')));
                $models = $fallback !== '' ? [$fallback] : [];
            }

            foreach ($models as $model) {
                $model = trim((string) $model);
                if ($model !== '') {
                    $ids[$model] = $model;
                }
            }
        }

        return array_values($ids);
    }

    /**
     * Per-model guidance shown to guru when choosing Asisten Aksara model.
     *
     * @return array<string, array{recommend: string, limit: string, tag: string}>
     */
    public static function modelGuides(): array
    {
        return [
            'llama-3.3-70b-versatile' => [
                'recommend' => 'Rekomendasi default: kualitas teks bacaan & patch materi seimbang.',
                'limit' => 'Kuota free Groq terbatas (RPM/RPD); cocok workshop, bukan beban tinggi.',
                'tag' => 'Direkomendasikan',
            ],
            'llama-3.1-8b-instant' => [
                'recommend' => 'Cepat untuk perbaikan singkat / klarifikasi chat.',
                'limit' => 'Kurang kuat untuk materi panjang atau STEM mendalam.',
                'tag' => 'Cepat',
            ],
            'deepseek-r1-distill-llama-70b' => [
                'recommend' => 'Baik untuk penalaran / langkah penyelesaian soal.',
                'limit' => 'Respons bisa lebih lambat; output reasoning kadang verbose.',
                'tag' => 'Penalaran',
            ],
            'mixtral-8x7b-32768' => [
                'recommend' => 'Konteks panjang — outline multi-seksi.',
                'limit' => 'Kualitas narasi bisa di bawah Llama 3.3 70B.',
                'tag' => 'Konteks panjang',
            ],
            'gemini-1.5-flash' => [
                'recommend' => 'Cepat & hemat untuk draf materi umum.',
                'limit' => 'Free tier Google punya kuota harian ketat.',
                'tag' => 'Hemat',
            ],
            'gemini-1.5-pro' => [
                'recommend' => 'Lebih kuat untuk materi padat / multi-seksi.',
                'limit' => 'Lebih mahal & kuota lebih ketat dari Flash.',
                'tag' => 'Kuat',
            ],
            'gemini-2.0-flash-exp' => [
                'recommend' => 'Eksperimental — coba fitur baru Gemini.',
                'limit' => 'Model eksperimen; perilaku bisa berubah.',
                'tag' => 'Eksperimen',
            ],
            'gpt-4o-mini' => [
                'recommend' => 'Stabil untuk draf materi & patch seksi.',
                'limit' => 'Berbayar; butuh saldo OpenAI Platform.',
                'tag' => 'Stabil',
            ],
            'gpt-4o' => [
                'recommend' => 'Kualitas tinggi untuk materi STEM / bahasa rumit.',
                'limit' => 'Biaya per token lebih tinggi dari mini.',
                'tag' => 'Premium',
            ],
            'gpt-3.5-turbo' => [
                'recommend' => 'Cadangan hemat bila kuota 4o habis.',
                'limit' => 'Kualitas & kepatuhan format JSON lebih lemah.',
                'tag' => 'Cadangan',
            ],
            'deepseek-chat' => [
                'recommend' => 'Hemat untuk teks bacaan panjang.',
                'limit' => 'Tidak mendukung generasi gambar; butuh saldo DeepSeek.',
                'tag' => 'Hemat',
            ],
            'deepseek-reasoner' => [
                'recommend' => 'Bagus untuk penjelasan langkah & STEM.',
                'limit' => 'Lebih lambat; biaya lebih tinggi dari chat.',
                'tag' => 'Penalaran',
            ],
            'claude-3-5-haiku-20241022' => [
                'recommend' => 'Cepat & rapi untuk prosa materi siswa.',
                'limit' => 'Berbayar Anthropic; konteks lebih kecil dari Sonnet.',
                'tag' => 'Cepat',
            ],
            'claude-3-5-sonnet-20241022' => [
                'recommend' => 'Kuat untuk materi mendalam & struktur seksi.',
                'limit' => 'Biaya tinggi; pantau kuota kredit.',
                'tag' => 'Premium',
            ],
            'llama3.2' => [
                'recommend' => 'Lokal via Ollama — tanpa biaya API.',
                'limit' => 'Tergantung hardware server; kualitas bervariasi.',
                'tag' => 'Lokal',
            ],
            'qwen2.5' => [
                'recommend' => 'Lokal; cukup baik untuk bahasa Indonesia.',
                'limit' => 'Perlu model terunduh di Ollama.',
                'tag' => 'Lokal',
            ],
            'mistral' => [
                'recommend' => 'Lokal ringan untuk draf singkat.',
                'limit' => 'Kurang ideal untuk materi multi-seksi panjang.',
                'tag' => 'Lokal',
            ],
            'deepseek-r1' => [
                'recommend' => 'Lokal untuk penalaran / STEM.',
                'limit' => 'Butuh RAM besar; latency tinggi.',
                'tag' => 'Lokal',
            ],
            'mock-model' => [
                'recommend' => 'Simulasi offline untuk uji UI.',
                'limit' => 'Bukan AI nyata — jangan dipakai workshop live.',
                'tag' => 'Simulasi',
            ],
            'custom-model' => [
                'recommend' => 'Endpoint kustom OpenAI-compatible.',
                'limit' => 'Kualitas & kuota sepenuhnya di infrastruktur Anda.',
                'tag' => 'Kustom',
            ],
        ];
    }

    /**
     * @return array{recommend: string, limit: string, tag: string}
     */
    public static function guideForModel(string $modelId): array
    {
        $guides = static::modelGuides();

        return $guides[$modelId] ?? [
            'recommend' => 'Model dari katalog provider aktif.',
            'limit' => 'Ikuti kuota & kebijakan vendor yang dikonfigurasi admin.',
            'tag' => 'Tersedia',
        ];
    }
}
