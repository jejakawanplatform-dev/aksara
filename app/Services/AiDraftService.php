<?php

namespace App\Services;

use App\Models\AiProvider;
use App\Models\AiUsageLog;
use App\Models\LearningPlan;
use App\Support\Ai\AiVendorProviderCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiDraftService
{
    public const FEATURE_PLAN = 'plan';

    public const FEATURE_MATERIAL = 'material';

    public const FEATURE_IMPROVE = 'improve';

    public const FEATURE_QUIZ = 'quiz';

    /**
     * Resolve model for a feature against a specific provider.
     * Uses feature setting only when the model is in that provider's catalog list (or custom vendor).
     */
    public function resolveModelFor(string $feature, AiProvider $provider, ?array $vendorMeta = null): string
    {
        $vendorMeta ??= $provider->catalogMeta() ?? AiVendorProviderCatalog::get($provider->vendor_key) ?? [];
        $catalogModels = $vendorMeta['models'] ?? [];
        $providerDefault = trim((string) ($provider->model ?: ($vendorMeta['default_model'] ?? 'gpt-4o-mini')));

        $recs = AiVendorProviderCatalog::featureModelRecommendations();
        $settingKey = $recs[$feature]['key'] ?? null;
        $featureModel = $settingKey ? trim((string) setting($settingKey, $recs[$feature]['default'] ?? '')) : '';

        if ($featureModel === '') {
            return $providerDefault !== '' ? $providerDefault : 'gpt-4o-mini';
        }

        if ($provider->is_custom || empty($catalogModels) || in_array($featureModel, $catalogModels, true)) {
            return $featureModel;
        }

        return $providerDefault !== '' ? $providerDefault : 'gpt-4o-mini';
    }

    /**
     * Human-readable label of the model the first usable active provider would use for a feature.
     */
    public function resolveActiveModelLabel(string $feature): string
    {
        foreach (AiProvider::active()->ordered()->get() as $provider) {
            if ($provider->vendor_key === 'mock' || ! $provider->isConfigured()) {
                continue;
            }

            $model = $this->resolveModelFor($feature, $provider);

            return $provider->name.' · '.$model;
        }

        return 'Belum ada provider AI aktif';
    }

    public function generateDraft(array $input): array
    {
        $startTime = microtime(true);

        // Retrieve active providers ordered by priority rank from ai_providers table
        $activeProviders = AiProvider::active()->ordered()->get();

        if ($activeProviders->isEmpty()) {
            Log::error('No active AI providers configured in DB.');
            throw new \RuntimeException('Tidak ada AI Provider yang aktif di pengaturan sistem.');
        }

        $lastException = null;
        $attemptCount = 0;

        foreach ($activeProviders as $provider) {
            $attemptCount++;
            $vendorId = $provider->vendor_key;
            $vendorMeta = $provider->catalogMeta() ?? AiVendorProviderCatalog::get($vendorId);

            if ($vendorId === 'mock') {
                continue; // Skip mock if it somehow gets here
            }

            $apiKey = trim((string) $provider->api_key);
            $baseUrl = trim((string) ($provider->base_url ?: ($vendorMeta['base_url'] ?? '')));
            $model = $this->resolveModelFor(self::FEATURE_PLAN, $provider, $vendorMeta);
            $timeout = (int) ($provider->timeout_seconds ?: 30);

            if (($vendorMeta['requires_key'] ?? true) && empty($apiKey)) {
                Log::warning("AI API key missing for provider [{$vendorId}], skipping to next provider in failover chain");

                continue;
            }

            try {
                $vendorStartTime = microtime(true);

                if ($vendorId === 'gemini') {
                    $result = $this->callGeminiApi($baseUrl, $apiKey, $model, $timeout, $input);
                } else {
                    $result = $this->callOpenAiCompatibleApi($baseUrl, $apiKey, $model, $timeout, $input);
                }

                $latencyMs = (int) round((microtime(true) - $vendorStartTime) * 1000);
                $status = ($attemptCount > 1) ? 'failover' : 'success';

                $this->logUsage(
                    $vendorId,
                    $model,
                    $result['usage']['prompt_tokens'] ?? 250,
                    $result['usage']['completion_tokens'] ?? 450,
                    $result['usage']['total_tokens'] ?? 700,
                    $status,
                    null,
                    $latencyMs
                );

                Log::info("AI Draft generated via provider [{$vendorId}] (#{$provider->priority_order})", [
                    'model' => $model,
                    'latency_ms' => $latencyMs,
                    'status' => $status,
                ]);

                return $result['data'];

            } catch (\Exception $e) {
                $lastException = $e;
                $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

                Log::warning("AI Provider [{$vendorId}] failed: ".$e->getMessage().'. Failing over to next provider in database chain...');

                $this->logUsage(
                    $vendorId,
                    $model,
                    0,
                    0,
                    0,
                    'error',
                    $e->getMessage(),
                    $latencyMs
                );
            }
        }

        // Global fallback if all providers in database fail
        Log::error('All database AI providers in failover chain failed.', ['last_error' => $lastException?->getMessage()]);
        throw new \RuntimeException('Semua AI Provider gagal memberikan respons yang valid. '.($lastException?->getMessage() ?? ''));
    }

    public function improveText(string $field, string $originalText, array $context): string
    {
        $startTime = microtime(true);
        $activeProviders = AiProvider::active()->ordered()->get();

        if ($activeProviders->isEmpty()) {
            throw new \RuntimeException('Tidak ada AI Provider yang aktif.');
        }

        $lastException = null;

        $system = "Anda adalah asisten kurikulum berbahasa Indonesia. Tugas Anda adalah menyempurnakan teks untuk {$field} rencana pembelajaran. "
            .'Jaga agar teks tetap ringkas, profesional, dan sesuai kaidah kurikulum pendidikan. '
            .'Kembalikan HANYA teks perbaikannya saja, tanpa penjelasan tambahan atau format Markdown (kecuali teks dasar).';

        $user = "Konteks Rencana Pembelajaran:\n"
            ."- Topik: {$context['topic']}\n"
            ."- Mata Pelajaran: {$context['subject']}\n"
            ."- Kelas: {$context['grade']} (Fase {$context['phase']})\n\n"
            ."Teks asli yang perlu disempurnakan:\n\"{$originalText}\"";

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];

        foreach ($activeProviders as $provider) {
            if ($provider->vendor_key === 'mock') {
                continue;
            }

            $vendorId = $provider->vendor_key;
            $vendorMeta = $provider->catalogMeta() ?? AiVendorProviderCatalog::get($vendorId);

            $apiKey = trim((string) $provider->api_key);
            $baseUrl = trim((string) ($provider->base_url ?: ($vendorMeta['base_url'] ?? '')));
            $model = $this->resolveModelFor(self::FEATURE_IMPROVE, $provider, $vendorMeta);
            $timeout = (int) ($provider->timeout_seconds ?: 15);

            if (($vendorMeta['requires_key'] ?? true) && empty($apiKey)) {
                continue;
            }

            try {
                $vendorStartTime = microtime(true);

                // Reusing call logic but modifying response expectation slightly since it's just plain text, not JSON
                if ($vendorId === 'gemini') {
                    $url = rtrim($baseUrl, '/')."/models/{$model}:generateContent?key={$apiKey}";
                    $response = Http::timeout($timeout)->retry(2, 1000)->post($url, [
                        'system_instruction' => ['parts' => [['text' => $system]]],
                        'contents' => [['parts' => [['text' => $user]]]],
                        'generationConfig' => ['temperature' => 0.5],
                    ]);

                    if (! $response->successful()) {
                        throw new \RuntimeException("Gemini API error (HTTP {$response->status()})");
                    }
                    $json = $response->json();
                    $resultText = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $usageMeta = $json['usageMetadata'] ?? [];
                    $promptTokens = $usageMeta['promptTokenCount'] ?? 100;
                    $completionTokens = $usageMeta['candidatesTokenCount'] ?? 50;

                } else {
                    $url = rtrim($baseUrl, '/').'/chat/completions';
                    $req = Http::timeout($timeout)->retry(2, 1000);
                    if (! empty($apiKey)) {
                        $req->withToken($apiKey);
                    }

                    $response = $req->post($url, [
                        'model' => $model,
                        'messages' => $messages,
                        'temperature' => 0.5,
                    ]);

                    if (! $response->successful()) {
                        throw new \RuntimeException("OpenAI-Compatible API error (HTTP {$response->status()})");
                    }
                    $json = $response->json();
                    $resultText = $json['choices'][0]['message']['content'] ?? '';
                    $usageMeta = $json['usage'] ?? [];
                    $promptTokens = $usageMeta['prompt_tokens'] ?? 100;
                    $completionTokens = $usageMeta['completion_tokens'] ?? 50;
                }

                $latencyMs = (int) round((microtime(true) - $vendorStartTime) * 1000);

                $this->logUsage(
                    $vendorId, $model, $promptTokens, $completionTokens, $promptTokens + $completionTokens,
                    'success', null, $latencyMs
                );

                return trim($resultText);

            } catch (\Exception $e) {
                $lastException = $e;
                $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
                $this->logUsage($vendorId, $model, 0, 0, 0, 'error', $e->getMessage(), $latencyMs);
            }
        }

        throw new \RuntimeException('Semua AI Provider gagal saat mencoba menyempurnakan teks. '.($lastException?->getMessage() ?? ''));
    }

    /**
     * Generasi Teks Bacaan Bahan Ajar Siswa Komprehensif via AI.
     */
    public function generateFullMaterialContent(LearningPlan $plan): array
    {
        $providers = AiProvider::active()->ordered()->get();

        $subjectName = $plan->subject->name ?? 'Mata Pelajaran';
        $objectives = is_array($plan->learning_objectives)
            ? implode(', ', $plan->learning_objectives)
            : $plan->learning_objectives;

        $systemPrompt = "Anda adalah penyusun Bahan Ajar Siswa kurikulum sekolah Indonesia yang ramah, jelas, dan edukatif.\n"
            ."Susunlah teks bacaan materi siswa yang lengkap, komprehensif, dan mudah dipahami.\n"
            ."Aturan HTML body: jangan menyisipkan tag <img> atau URL gambar fiktif/eksternal.\n"
            ."Kembalikan HANYA JSON valid dengan struktur:\n"
            ."{\n"
            ."  \"title\": \"Judul Bahan Ajar Bacaan Siswa\",\n"
            ."  \"sections\": [\n"
            ."    {\n"
            ."      \"heading\": \"1. Pengantar & Konsep Utama\",\n"
            ."      \"body\": \"<p>Penjelasan paragraf komprehensif 2-3 paragraf mengenai konsep utama materi ini...</p>\"\n"
            ."    },\n"
            ."    {\n"
            ."      \"heading\": \"2. Pembahasan Mendalam & Contoh Praktis\",\n"
            ."      \"body\": \"<p>Penjelasan langkah demi langkah, contoh penerapan nyata, atau kode/kasus...</p>\"\n"
            ."    },\n"
            ."    {\n"
            ."      \"heading\": \"3. Aktivitas & Rangkuman Pembelajaran\",\n"
            ."      \"body\": \"<p>Rangkuman poin-poin penting dan petunjuk eksperimen/diskusi kelas...</p>\"\n"
            ."    }\n"
            ."  ],\n"
            ."  \"reflectionQuestion\": [\n"
            ."    \"Pertanyaan refleksi 1 untuk menguji pemahaman siswa\",\n"
            ."    \"Pertanyaan refleksi 2 mengenai penerapan dalam kehidupan sehari-hari\"\n"
            ."  ]\n"
            .'}';

        $userPrompt = "Susun Bahan Ajar Bacaan Siswa Lengkap:\n"
            ."- Topik: {$plan->topic}\n"
            ."- Mata Pelajaran: {$subjectName}\n"
            ."- Kelas/Fase: Kelas {$plan->grade} (Fase {$plan->phase})\n"
            ."- Tujuan Pembelajaran: {$objectives}";

        foreach ($providers as $provider) {
            $vendorId = $provider->vendor_key;
            $vendorMeta = $provider->catalogMeta() ?? AiVendorProviderCatalog::get($vendorId);

            $apiKey = trim((string) $provider->api_key);
            $baseUrl = trim((string) ($provider->base_url ?: ($vendorMeta['base_url'] ?? '')));
            $model = $this->resolveModelFor(self::FEATURE_MATERIAL, $provider, $vendorMeta);
            $timeout = (int) ($provider->timeout_seconds ?: 30);

            if (($vendorMeta['requires_key'] ?? true) && empty($apiKey)) {
                continue;
            }

            try {
                $messages = [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ];

                $req = Http::timeout($timeout)->retry(2, 1000);
                if (! empty($apiKey)) {
                    $req->withToken($apiKey);
                }

                $response = $req->post(rtrim($baseUrl, '/').'/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.7,
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $contentStr = $json['choices'][0]['message']['content'] ?? '';
                    $data = json_decode($contentStr, true);

                    if (is_array($data) && ! empty($data['sections']) && is_array($data['sections'])) {
                        return [
                            'title' => $data['title'] ?? "Bahan Ajar: {$plan->topic}",
                            'sections' => $data['sections'],
                            'reflectionQuestion' => is_array($data['reflectionQuestion'] ?? null)
                                ? $data['reflectionQuestion']
                                : (array) ($data['reflectionQuestion'] ?? ["Apa yang dapat disimpulkan dari {$plan->topic}?"]),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("AI Provider [{$vendorId}] failed material generation: {$e->getMessage()}");
            }
        }

        // Fallback default structured material content
        return [
            'title' => "Bahan Ajar: {$plan->topic}",
            'sections' => [
                [
                    'heading' => "1. Pengenalan {$plan->topic}",
                    'body' => "<p>Topik <strong>{$plan->topic}</strong> merupakan bagian penting dari mata pelajaran {$subjectName}. Dalam bab ini, kita akan mempelajari konsep-konsep dasar serta bagaimana menerapkannya secara efektif dalam pemecahan masalah.</p><p>Pemahaman yang mendalam mengenai topik ini akan membantu siswa mencapai Tujuan Pembelajaran: <em>{$objectives}</em>.</p>",
                ],
                [
                    'heading' => '2. Konsep Utama & Contoh Penerapan',
                    'body' => "<p>Mari kita cermati elemen-elemen penting dalam <strong>{$plan->topic}</strong>:</p><ul><li><strong>Konsep Dasar:</strong> Identifikasi masalah dan struktur utama topik.</li><li><strong>Pendekatan Solusi:</strong> Langkah-langkah sistematis untuk menyelesaikan tantangan terkait.</li><li><strong>Penerapan Nyata:</strong> Penggunaan konsep ini dalam kehidupan sehari-hari dan proyek sekolah.</li></ul>",
                ],
                [
                    'heading' => '3. Rangkuman & Pembahasan Sesi',
                    'body' => "<p>Secara ringkas, <strong>{$plan->topic}</strong> membekali siswa dengan pola pikir kritis dan terstruktur. Diskusikan dengan teman sekelompokmu mengenai tantangan terpenting yang kalian temui selama pembelajaran.</p>",
                ],
            ],
            'reflectionQuestion' => [
                "Bagian mana dari topik {$plan->topic} yang paling menarik untuk kamu pelajari?",
                "Bagaimana kamu akan menerapkan konsep {$plan->topic} ini dalam kehidupan sehari-hari?",
            ],
        ];
    }

    /**
     * Obrolan 2-Arah AI Co-Pilot untuk Refinement Bahan Ajar.
     *
     * @param  array{intent?: string, title?: string, sectionCount?: int, sections?: array, reflections?: string}  $editorContext
     */
    public function chatRefineMaterial(LearningPlan $plan, array $chatHistory, string $userMessage, array $selectedTemplates = [], array $editorContext = []): array
    {
        $providers = AiProvider::active()->ordered()->get();

        $subjectName = $plan->subject->name ?? 'Mata Pelajaran';
        $objectives = is_array($plan->learning_objectives)
            ? implode(', ', $plan->learning_objectives)
            : $plan->learning_objectives;

        $intent = $editorContext['intent'] ?? 'create';
        if (! in_array($intent, ['create', 'patch', 'rewrite'], true)) {
            $intent = 'create';
        }

        $presetDirectives = [];
        if (! empty($selectedTemplates['illustrations'])) {
            $presetDirectives[] = '- Sertakan rekomendasi ilustrasi visual di setiap seksi sebagai blok HTML berikut (bukan tag img): <blockquote><p><strong>Ilustrasi:</strong> deskripsi visual terperinci...</p></blockquote>. DILARANG memakai tag <img> atau URL file fiktif.';
        }
        if (! empty($selectedTemplates['illustration_links'])) {
            $presetDirectives[] = '- Di SETIAP seksi yang diubah/dihasilkan, sisipkan PERSIS 1 blok HTML berikut (bukan plain text, bukan tag img). '
                .'Blok ini HANYA untuk guru (bantuan authoring), WAJIB berisi Prompt AI Image dalam tag <code> agar mudah disalin: '
                .'<blockquote>'
                .'<p><strong>🖼️ Saran Ilustrasi:</strong> deskripsi singkat bahasa Indonesia.</p>'
                .'<p><strong>🎯 Prompt AI Image:</strong> <code>detailed English prompt ready to paste into an external AI image generator</code></p>'
                .'<p><a href="https://unsplash.com/s/photos/kata-kunci">Cari &amp; unduh di Unsplash</a> · '
                .'<a href="https://commons.wikimedia.org/w/index.php?search=kata+kunci&amp;title=Special:MediaSearch&amp;type=image">Cari di Wikimedia Commons</a></p>'
                .'<p><em>Sumber: Unsplash / Wikimedia Commons (lisensi bebas). Salin prompt atau unduh, lalu unggah lewat tombol Gambar.</em></p>'
                .'</blockquote>. '
                .'WAJIB ada baris Prompt AI Image (bahasa Inggris, spesifik, di dalam <code>). '
                .'DILARANG menulis "Saran ilustrasi: ID: ..., Prompt AI Image EN: ..." sebagai teks polos. '
                .'DILARANG tag <img> atau hotlink file .jpg/.png.';
        }
        if (! empty($selectedTemplates['references'])) {
            $presetDirectives[] = '- Sertakan referensi sumber belajar terpercaya bila membuat materi lengkap.';
        }
        if (! empty($selectedTemplates['case_studies'])) {
            $presetDirectives[] = '- Sertakan studi kasus kontekstual Indonesia.';
        }
        if (! empty($selectedTemplates['stem_code'])) {
            $presetDirectives[] = '- Sertakan rumus KaTeX dan/atau blok kode bila relevan.';
        }
        if (! empty($selectedTemplates['glossary'])) {
            $presetDirectives[] = '- Tambahkan glosarium istilah penting bila relevan.';
        }

        $directivesStr = count($presetDirectives) > 0
            ? "\nPetunjuk Khusus Pengayaan:\n".implode("\n", $presetDirectives)
            : '';

        $intentRules = match ($intent) {
            'patch' => 'Mode INTENT=patch: Materi sudah ada. JANGAN menulis ulang seluruh dokumen. Di materialData.sections HANYA sertakan seksi yang diubah/ditambah. Set applyMode="patch".',
            'rewrite' => 'Mode INTENT=rewrite: Susun ulang seluruh materi. Hasilkan materialData lengkap dan applyMode="rewrite".',
            default => 'Mode INTENT=create: Materi kosong/placeholder. Usulkan proposedOutline dulu bila perlu. Bila guru setuju, hasilkan materialData lengkap dengan applyMode="create". Jika belum setuju, materialData boleh null.',
        };

        $systemPrompt = "Anda adalah Asisten AI Pedagogical Co-Pilot untuk guru Indonesia.\n"
            ."Bersikaplah seperti agen percakapan: klarifikasi, usulkan rencana, baru menghasilkan draf siap terapkan.\n"
            .$intentRules."\n"
            .'HTML body: tag aman (p, strong, em, ul, ol, li, h2, h3, blockquote, table, code, pre, a). JANGAN tag <img>.'."\n"
            ."Kembalikan HANYA JSON:\n"
            .'{"replyMessage":"...","applyMode":"create|patch|rewrite","proposedOutline":["..."],"materialData":{"title":"...","sections":[{"heading":"...","body":"<p>...</p>"}],"reflectionQuestion":["..."]}}'
            ."\n".$directivesStr;

        $editorSummary = $this->formatEditorContextForPrompt($editorContext);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Konteks Rencana:\n- Topik: {$plan->topic}\n- Mapel: {$subjectName}\n- Tujuan: {$objectives}\n\n{$editorSummary}"],
        ];

        foreach ($chatHistory as $msg) {
            if (! empty($msg['role']) && ! empty($msg['content']) && is_string($msg['content'])) {
                $messages[] = [
                    'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                    'content' => $msg['content'],
                ];
            }
        }

        $messages[] = ['role' => 'user', 'content' => "Instruksi Guru: {$userMessage}"];

        foreach ($providers as $provider) {
            $vendorId = $provider->vendor_key;
            if ($vendorId === 'mock') {
                continue;
            }
            $vendorMeta = $provider->catalogMeta() ?? AiVendorProviderCatalog::get($vendorId);
            $apiKey = trim((string) $provider->api_key);
            $baseUrl = trim((string) ($provider->base_url ?: ($vendorMeta['base_url'] ?? '')));
            $model = $this->resolveModelFor(self::FEATURE_MATERIAL, $provider, $vendorMeta);
            $timeout = (int) ($provider->timeout_seconds ?: 30);

            if (($vendorMeta['requires_key'] ?? true) && empty($apiKey)) {
                continue;
            }

            try {
                $req = Http::timeout($timeout)->retry(2, 1000);
                if (! empty($apiKey)) {
                    $req->withToken($apiKey);
                }

                $response = $req->post(rtrim($baseUrl, '/').'/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.7,
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $contentStr = $json['choices'][0]['message']['content'] ?? '';
                    $data = json_decode($contentStr, true);

                    if (is_array($data)) {
                        $materialData = (! empty($data['materialData']) && is_array($data['materialData']))
                            ? $data['materialData']
                            : null;
                        $applyMode = $data['applyMode'] ?? $intent;
                        if (! in_array($applyMode, ['create', 'patch', 'rewrite'], true)) {
                            $applyMode = $intent;
                        }

                        return [
                            'replyMessage' => $data['replyMessage'] ?? 'Bahan Ajar telah disesuaikan berdasarkan instruksi Anda.',
                            'materialData' => $materialData,
                            'proposedOutline' => is_array($data['proposedOutline'] ?? null) ? $data['proposedOutline'] : null,
                            'applyMode' => $applyMode,
                            'modelLabel' => $provider->name.' · '.$model,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("AI Provider [{$vendorId}] failed material chat refinement: {$e->getMessage()}");
            }
        }

        return $this->fallbackCopilotResponse($plan, $userMessage, $subjectName, $intent, $editorContext);
    }

    /**
     * @param  array{intent?: string, title?: string, sectionCount?: int, sections?: array, reflections?: string}  $editorContext
     */
    private function formatEditorContextForPrompt(array $editorContext): string
    {
        $intent = $editorContext['intent'] ?? 'create';
        $title = $editorContext['title'] ?? '(belum ada)';
        $count = (int) ($editorContext['sectionCount'] ?? 0);
        $lines = [
            "Konteks Editor Guru (INTENT={$intent}):",
            "- Judul: {$title}",
            "- Jumlah seksi: {$count}",
        ];

        foreach ($editorContext['sections'] ?? [] as $i => $sec) {
            $heading = $sec['heading'] ?? ('Seksi '.($i + 1));
            $excerpt = $sec['bodyExcerpt'] ?? '';
            $lines[] = '- ['.$i.'] '.$heading.($excerpt !== '' ? " — cuplikan: {$excerpt}" : ' — (kosong/placeholder)');
        }

        if (! empty($editorContext['reflections'])) {
            $lines[] = '- Refleksi: '.$editorContext['reflections'];
        }

        return implode("\n", $lines);
    }

    private function fallbackCopilotResponse(LearningPlan $plan, string $userMessage, string $subjectName, string $intent, array $editorContext): array
    {
        if ($intent === 'patch' && ! empty($editorContext['sections'])) {
            $firstHeading = $editorContext['sections'][0]['heading'] ?? '1. Seksi';

            return [
                'replyMessage' => 'Saya menyiapkan perbaikan seksi terkait instruksi Anda (mode patch). Silakan terapkan bila setuju.',
                'applyMode' => 'patch',
                'proposedOutline' => null,
                'materialData' => [
                    'title' => $editorContext['title'] ?? "Bahan Ajar: {$plan->topic}",
                    'sections' => [
                        [
                            'heading' => $firstHeading,
                            'body' => '<p>Perbaikan berdasarkan instruksi: <em>'.e($userMessage).'</em>. Topik <strong>'.$plan->topic.'</strong> ('.$subjectName.').</p>',
                        ],
                    ],
                ],
                'modelLabel' => 'Fallback lokal',
            ];
        }

        $outline = [
            "1. Pengantar Konsep {$plan->topic}",
            '2. Pembahasan & Penerapan Kontekstual',
            '3. Rangkuman & Refleksi',
        ];

        if ($intent === 'create') {
            return [
                'replyMessage' => "Mari kita rancang outline materi '{$plan->topic}'. Setuju dengan 3 seksi berikut? Jika ya, minta saya menyusun teks lengkapnya.",
                'applyMode' => 'create',
                'proposedOutline' => $outline,
                'materialData' => null,
                'modelLabel' => 'Fallback lokal',
            ];
        }

        return [
            'replyMessage' => "Saya menyusun ulang materi '{$plan->topic}' sesuai instruksi Anda.",
            'applyMode' => 'rewrite',
            'proposedOutline' => $outline,
            'materialData' => [
                'title' => "Bahan Ajar: {$plan->topic}",
                'sections' => [
                    [
                        'heading' => "1. Pengantar Konsep {$plan->topic}",
                        'body' => '<p>Materi diperbarui berdasarkan: <em>'.e($userMessage).'</em>.</p><p>Topik <strong>'.$plan->topic.'</strong> ('.$subjectName.').</p>',
                    ],
                    [
                        'heading' => '2. Pembahasan & Penerapan Kontekstual',
                        'body' => '<p>Langkah dan studi kasus:</p><ul><li>Memahami tantangan utama.</li><li>Mengembangkan solusi terstruktur.</li></ul>',
                    ],
                    [
                        'heading' => '3. Rangkuman & Refleksi',
                        'body' => '<p>Rangkuman poin penting dan diskusi kelompok.</p>',
                    ],
                ],
                'reflectionQuestion' => [
                    "Apa bagian tersulit dari {$plan->topic}?",
                    "Bagaimana menerapkan {$plan->topic} sehari-hari?",
                ],
            ],
            'modelLabel' => 'Fallback lokal',
        ];
    }

    private function logUsage(string $vendorId, string $model, int $promptTokens, int $completionTokens, int $totalTokens, string $status, ?string $errorMessage = null, int $latencyMs = 0): void
    {
        try {
            $meta = AiVendorProviderCatalog::get($vendorId);
            $inputRate = $meta['cost_per_1k_input'] ?? 0;
            $outputRate = $meta['cost_per_1k_output'] ?? 0;

            $cost = ($promptTokens * $inputRate / 1000) + ($completionTokens * $outputRate / 1000);

            AiUsageLog::create([
                'user_id' => Auth::id(),
                'vendor_id' => $vendorId,
                'model' => $model,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'cost_estimate_usd' => $cost,
                'latency_ms' => $latencyMs,
                'status' => $status,
                'error_message' => $errorMessage,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log AI usage', ['error' => $e->getMessage()]);
        }
    }

    private function callGeminiApi(string $baseUrl, string $apiKey, string $model, int $timeout, array $input): array
    {
        $url = rtrim($baseUrl, '/')."/models/{$model}:generateContent?key={$apiKey}";

        $promptText = $this->buildMessages($input)[1]['content'];
        $systemText = $this->buildMessages($input)[0]['content'];

        $response = Http::timeout($timeout)
            ->retry(2, 1000)
            ->post($url, [
                'system_instruction' => [
                    'parts' => [['text' => $systemText]],
                ],
                'contents' => [
                    ['parts' => [['text' => $promptText]]],
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'temperature' => 0.7,
                ],
            ]);

        if (! $response->successful()) {
            Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException("Gemini API error (HTTP {$response->status()})");
        }

        $json = $response->json();
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $data = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            throw new \RuntimeException('Format JSON Gemini tidak valid.');
        }

        $usageMeta = $json['usageMetadata'] ?? [];

        return [
            'data' => $this->validateAndFillSchema($data, $input),
            'usage' => [
                'prompt_tokens' => $usageMeta['promptTokenCount'] ?? 250,
                'completion_tokens' => $usageMeta['candidatesTokenCount'] ?? 450,
                'total_tokens' => $usageMeta['totalTokenCount'] ?? 700,
            ],
        ];
    }

    private function callOpenAiCompatibleApi(string $baseUrl, string $apiKey, string $model, int $timeout, array $input): array
    {
        $url = rtrim($baseUrl, '/').'/chat/completions';

        $req = Http::timeout($timeout)->retry(2, 1000);
        if (! empty($apiKey)) {
            $req->withToken($apiKey);
        }

        $response = $req->post($url, [
            'model' => $model,
            'messages' => $this->buildMessages($input),
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.7,
        ]);

        if (! $response->successful()) {
            Log::error('OpenAI-Compatible API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException("OpenAI-Compatible API error (HTTP {$response->status()})");
        }

        $json = $response->json();
        $content = $json['choices'][0]['message']['content'] ?? '';
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            throw new \RuntimeException('Format JSON AI tidak valid.');
        }

        $usageMeta = $json['usage'] ?? [];

        return [
            'data' => $this->validateAndFillSchema($data, $input),
            'usage' => [
                'prompt_tokens' => $usageMeta['prompt_tokens'] ?? 250,
                'completion_tokens' => $usageMeta['completion_tokens'] ?? 450,
                'total_tokens' => $usageMeta['total_tokens'] ?? 700,
            ],
        ];
    }

    private function validateAndFillSchema(array $data, array $input): array
    {
        $cpDraft = $data['cpDraft'] ?? "Draf CP: Understanding {$input['topic']}";
        if (is_array($cpDraft)) {
            $cpDraft = isset($cpDraft['statement'])
                ? (string) $cpDraft['statement']
                : implode("\n", array_filter(array_map(fn ($v) => is_string($v) ? $v : json_encode($v), $cpDraft)));
        }

        return [
            'cpDraft' => (string) $cpDraft,
            'tpDraft' => is_array($data['tpDraft'] ?? null) ? $data['tpDraft'] : ["TP: Memahami {$input['topic']}"],
            'atpDraft' => is_array($data['atpDraft'] ?? null) ? $data['atpDraft'] : [
                ['sequence' => 1, 'activity' => 'Pengantar'],
                ['sequence' => 2, 'activity' => 'Eksplorasi'],
            ],
            'lessonPlanDraft' => is_array($data['lessonPlanDraft'] ?? null) ? $data['lessonPlanDraft'] : [
                'opening' => ['Salam', 'Apersepsi'],
                'core' => ["Pembahasan {$input['topic']}"],
                'closing' => ['Kesimpulan'],
                'assessmentPlan' => ['Observasi'],
            ],
            'learningMaterialDraft' => is_array($data['learningMaterialDraft'] ?? null) ? $data['learningMaterialDraft'] : [
                'title' => "Materi: {$input['topic']}",
                'sections' => [
                    ['heading' => 'Pendahuluan', 'body' => "Penjelasan materi {$input['topic']}."],
                ],
                'reflectionQuestion' => "Apa yang dipelajari dari {$input['topic']}?",
            ],
            'reviewNotes' => is_array($data['reviewNotes'] ?? null) ? $data['reviewNotes'] : [
                'Draf AI telah diproses — silakan diperiksa dan disesuaikan oleh guru.',
            ],
        ];
    }

    private function buildMessages(array $input): array
    {
        $system = "Anda adalah asisten penyusunan draf modul ajar kurikulum sekolah berbahasa Indonesia.\n"
            .'Kembalikan HANYA JSON valid dengan key: cpDraft, tpDraft (array of strings), atpDraft (array of objects {sequence, activity}), '
            .'lessonPlanDraft (object {opening, core, closing, assessmentPlan}), learningMaterialDraft (object {title, sections, reflectionQuestion}), reviewNotes (array of strings).';

        $user = "Buat draf modul ajar:\n"
            ."- Fase/Kelas: {$input['phase']} / Kelas {$input['grade']}\n"
            ."- Mata Pelajaran: {$input['subject']}\n"
            ."- Topik: {$input['topic']}\n"
            ."- Durasi: {$input['duration_minutes']} menit\n"
            ."- Tujuan Pembelajaran: {$input['learning_objectives']}\n"
            ."- Kebutuhan Belajar: {$input['student_needs']}\n"
            ."- Referensi Kurikulum: {$input['curriculum_reference']}";

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];
    }
}
