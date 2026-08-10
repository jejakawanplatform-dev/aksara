<?php

namespace App\Http\Controllers\Materials;

use App\Enums\MaterialStatus;
use App\Enums\PlanStatus;
use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\LearningMaterial;
use App\Services\AiDraftService;
use App\Services\MaterialImageService;
use App\Support\MaterialContentHtml;
use App\Support\SubjectContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MaterialEditController extends Controller
{
    public function edit(LearningMaterial $material, AiDraftService $aiService): Response
    {
        $this->authorizeEditor($material);

        $material->load(['plan.subject', 'plan.class']);
        $content = is_array($material->content) ? $material->content : [];
        $topic = $material->plan->topic ?? 'Bahan Ajar';

        $rawSections = $content['sections'] ?? ($content['material']['sections'] ?? []);
        $sections = [];
        foreach ($rawSections as $sec) {
            if (is_array($sec)) {
                $sections[] = [
                    'heading' => $sec['heading'] ?? '',
                    'body' => MaterialContentHtml::sanitizeSectionBody((string) ($sec['body'] ?? '')),
                ];
            } else {
                $sections[] = [
                    'heading' => (string) $sec,
                    'body' => '',
                ];
            }
        }

        if ($sections === []) {
            $sections[] = [
                'heading' => '1. Pengantar Pembelajaran',
                'body' => "<p>Isi teks materi pembelajaran untuk topik <strong>{$topic}</strong> di sini.</p>",
            ];
        }

        $rawReflections = $content['reflectionQuestion'] ?? ($content['material']['reflectionQuestion'] ?? []);
        $reflectionsText = is_array($rawReflections)
            ? implode("\n", $rawReflections)
            : (string) $rawReflections;

        $canGenerateImages = AiProvider::hasConfiguredImageGeneration();

        return Inertia::render('Materials/Edit', [
            'material' => [
                'id' => $material->id,
                'status' => $material->status->value ?? 'draft',
                'plan' => [
                    'id' => $material->plan->id,
                    'topic' => $topic,
                    'grade' => $material->plan->grade,
                    'subject' => $material->plan->subject?->name,
                    'className' => $material->plan->class?->name,
                ],
            ],
            'form' => [
                'title' => $content['title'] ?? $topic,
                'sections' => $sections,
                'reflectionsText' => $reflectionsText,
            ],
            'isStem' => SubjectContext::isStem($material->plan->subject),
            'canGenerateImages' => $canGenerateImages,
            'activeModelLabel' => $aiService->resolveActiveModelLabel(AiDraftService::FEATURE_MATERIAL),
            'endpoints' => [
                'update' => route('materials.update', $material),
                'publish' => route('materials.publish', $material),
                'images' => route('materials.images', $material),
                'media' => route('materials.media', $material),
                'mediaDestroyBase' => url('/materials/'.$material->id.'/media'),
                'copilot' => route('materials.copilot', $material),
                'show' => route('materials.show', $material),
            ],
        ]);
    }

    public function indexMedia(LearningMaterial $material, MaterialImageService $images): JsonResponse
    {
        $this->authorizeEditor($material);

        return response()->json([
            'items' => $images->list($material),
        ]);
    }

    public function destroyMedia(LearningMaterial $material, string $filename, MaterialImageService $images): JsonResponse
    {
        $this->authorizeEditor($material);

        try {
            $images->delete($material, $filename);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['filename' => [$e->getMessage()]],
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['filename' => [$e->getMessage()]],
            ], 422);
        }

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, LearningMaterial $material): RedirectResponse
    {
        $this->authorizeEditor($material);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sections' => 'required|array|min:1',
            'sections.*.heading' => 'required|string|max:255',
            'sections.*.body' => 'nullable|string',
            'reflectionsText' => 'nullable|string',
        ]);

        $reflections = array_values(array_filter(array_map(
            'trim',
            explode("\n", (string) ($validated['reflectionsText'] ?? ''))
        )));

        $sections = MaterialContentHtml::sanitizeSections($validated['sections']);

        $material->update([
            'content' => [
                'title' => $validated['title'],
                'sections' => $sections,
                'reflectionQuestion' => $reflections,
            ],
            'status' => MaterialStatus::Draft,
        ]);

        return back()->with('message', 'Draf Bahan Ajar berhasil disimpan.');
    }

    public function publish(Request $request, LearningMaterial $material): RedirectResponse
    {
        $this->update($request, $material);

        $material->refresh();
        $material->update([
            'status' => MaterialStatus::Published,
            'published_at' => now(),
        ]);

        $material->plan->update([
            'status' => PlanStatus::Published,
        ]);

        return redirect()
            ->route('materials.show', $material)
            ->with('message', 'Bahan Ajar resmi diterbitkan ke Siswa!');
    }

    public function storeImage(Request $request, LearningMaterial $material, MaterialImageService $images): JsonResponse
    {
        $this->authorizeEditor($material);

        $validated = $request->validate([
            'dataUrl' => 'required|string',
            'originalName' => 'nullable|string|max:255',
        ]);

        $dataUrl = trim($validated['dataUrl']);
        if ($dataUrl === '' || ! str_starts_with($dataUrl, 'data:image/')) {
            throw ValidationException::withMessages([
                'dataUrl' => 'Data gambar tidak valid.',
            ]);
        }

        if (strlen($dataUrl) > (int) (3.5 * 1024 * 1024)) {
            throw ValidationException::withMessages([
                'dataUrl' => 'Gambar terlalu besar. Kompresi gagal mengecilkan file.',
            ]);
        }

        try {
            $decoded = $images->decodeDataUrl($dataUrl);
            $url = $images->storeBinary($material, $decoded['binary'], $decoded['extension']);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            throw ValidationException::withMessages([
                'dataUrl' => $e->getMessage(),
            ]);
        }

        return response()->json(['url' => $url]);
    }

    public function copilot(Request $request, LearningMaterial $material, AiDraftService $aiService): JsonResponse
    {
        $this->authorizeEditor($material);
        $material->load('plan.subject');

        $validated = $request->validate([
            'message' => 'required|string|max:8000',
            'history' => 'nullable|array',
            'history.*.role' => 'required|string|in:user,assistant',
            'history.*.content' => 'required|string',
            'templates' => 'nullable|array',
            'title' => 'nullable|string|max:255',
            'sections' => 'nullable|array',
            'sections.*.heading' => 'nullable|string',
            'sections.*.body' => 'nullable|string',
            'reflectionsText' => 'nullable|string',
        ]);

        $input = trim($validated['message']);
        $sections = $validated['sections'] ?? [];
        $title = (string) ($validated['title'] ?? '');
        $reflectionsText = (string) ($validated['reflectionsText'] ?? '');
        $templates = $validated['templates'] ?? [
            'illustrations' => false,
            'illustration_links' => false,
            'references' => true,
            'case_studies' => false,
            'stem_code' => false,
            'glossary' => false,
        ];

        $canGenerateImages = AiProvider::hasConfiguredImageGeneration();
        if (! $canGenerateImages) {
            $templates['illustrations'] = false;
        }

        $intent = $this->detectCopilotIntent($input, $sections);
        $editorContext = $this->buildEditorContext($intent, $title, $sections, $reflectionsText);

        $history = array_map(static fn ($m) => [
            'role' => $m['role'],
            'content' => $m['content'],
        ], $validated['history'] ?? []);

        $history[] = ['role' => 'user', 'content' => $input];

        $res = $aiService->chatRefineMaterial(
            $material->plan,
            $history,
            $input,
            $templates,
            $editorContext
        );

        $materialData = $res['materialData'] ?? null;
        if (is_array($materialData) && isset($materialData['sections'])) {
            $materialData['sections'] = MaterialContentHtml::sanitizeSections($materialData['sections']);
        }

        $applyMode = $res['applyMode'] ?? $intent;
        if (! in_array($applyMode, ['create', 'patch', 'rewrite'], true)) {
            $applyMode = $intent;
        }

        return response()->json([
            'replyMessage' => $res['replyMessage'] ?? 'Bahan ajar telah disesuaikan.',
            'materialData' => $materialData,
            'proposedOutline' => $res['proposedOutline'] ?? null,
            'applyMode' => $applyMode,
            'intent' => $intent,
            'modelLabel' => $res['modelLabel'] ?? $aiService->resolveActiveModelLabel(AiDraftService::FEATURE_MATERIAL),
            'canGenerateImages' => $canGenerateImages,
        ]);
    }

    private function authorizeEditor(LearningMaterial $material): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $material->plan->teacher_id === $user->id), 403);
    }

    /**
     * @param  list<array{heading?: string, body?: string}>  $sections
     */
    private function isMaterialEssentiallyEmpty(array $sections): bool
    {
        if ($sections === []) {
            return true;
        }

        $placeholders = [
            'tuliskan penjelasan',
            'isi teks materi',
            'sub-topik baru',
            'penjelasan materi lengkap',
        ];

        foreach ($sections as $sec) {
            $plain = mb_strtolower(trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($sec['body'] ?? ''))) ?? ''));
            if ($plain === '') {
                continue;
            }

            $isPlaceholder = false;
            foreach ($placeholders as $ph) {
                if (str_contains($plain, $ph)) {
                    $isPlaceholder = true;
                    break;
                }
            }

            if (! $isPlaceholder && mb_strlen($plain) > 60) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array{heading?: string, body?: string}>  $sections
     */
    private function detectCopilotIntent(string $userMessage, array $sections): string
    {
        $msg = mb_strtolower(trim($userMessage));
        $rewriteHints = [
            'susun ulang semua',
            'tulis ulang semua',
            'generate ulang',
            'buat ulang seluruh',
            'rewrite all',
            'ganti semua',
            'mulai dari awal',
        ];

        foreach ($rewriteHints as $hint) {
            if ($msg !== '' && str_contains($msg, $hint)) {
                return 'rewrite';
            }
        }

        if ($this->isMaterialEssentiallyEmpty($sections)) {
            return 'create';
        }

        return 'patch';
    }

    /**
     * @param  list<array{heading?: string, body?: string}>  $sections
     * @return array{intent: string, title: string, sectionCount: int, sections: list<array{heading: string, bodyExcerpt: string}>, reflections: string}
     */
    private function buildEditorContext(string $intent, string $title, array $sections, string $reflectionsText): array
    {
        $out = [];
        foreach ($sections as $sec) {
            $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($sec['body'] ?? ''))) ?? '');
            $excerpt = mb_strlen($plain) > 280 ? mb_substr($plain, 0, 280).'…' : $plain;
            $out[] = [
                'heading' => (string) ($sec['heading'] ?? ''),
                'bodyExcerpt' => $excerpt,
            ];
        }

        return [
            'intent' => $intent,
            'title' => $title,
            'sectionCount' => count($sections),
            'sections' => $out,
            'reflections' => mb_substr(trim($reflectionsText), 0, 400),
        ];
    }
}
