<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\AiUsageLog;
use App\Services\SettingService;
use App\Support\Access\PermissionCatalog;
use App\Support\Ai\AiVendorProviderCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request, SettingService $service): Response
    {
        abort_unless(auth()->user()?->can(PermissionCatalog::SETTINGS_MANAGE), 403);

        $tab = (string) $request->query('tab', 'ai');
        if (! in_array($tab, ['ai', 'security', 'features'], true)) {
            $tab = 'ai';
        }

        $recs = AiVendorProviderCatalog::featureModelRecommendations();

        $providers = AiProvider::ordered()->get()->map(function (AiProvider $p) {
            $meta = $p->catalogMeta();

            return [
                'id' => $p->id,
                'vendor_key' => $p->vendor_key,
                'name' => $p->name,
                'is_active' => (bool) $p->is_active,
                'priority_order' => $p->priority_order,
                'api_key' => $p->api_key,
                'base_url' => $p->base_url,
                'model' => $p->model,
                'max_tokens' => $p->max_tokens,
                'temperature' => (float) $p->temperature,
                'timeout_seconds' => $p->timeout_seconds,
                'is_custom' => (bool) $p->is_custom,
                'catalogModels' => $meta['models'] ?? [],
                'meta' => [
                    'badge' => $meta['badge'] ?? null,
                    'badge_color' => $meta['badge_color'] ?? null,
                    'requires_key' => $meta['requires_key'] ?? false,
                ],
            ];
        });

        $todayLogs = AiUsageLog::whereDate('created_at', now()->today())->get();

        return Inertia::render('Settings/Index', [
            'pageTitle' => 'Pengaturan Sistem Global',
            'activeTab' => $tab,
            'settings' => [
                'ai_provider' => (string) $service->get('ai.provider', 'gemini'),
                'ai_daily_limit_per_teacher' => (int) $service->get('ai.daily_limit_per_teacher', 20),
                'ai_anonymize_student_data' => (bool) $service->get('ai.anonymize_student_data', true),
                'ai_model_plan' => (string) $service->get('ai.model_plan', $recs['plan']['default']),
                'ai_model_material' => (string) $service->get('ai.model_material', $recs['material']['default']),
                'ai_model_improve' => (string) $service->get('ai.model_improve', $recs['improve']['default']),
                'ai_model_quiz' => (string) $service->get('ai.model_quiz', $recs['quiz']['default']),
                'security_allow_public_registration' => (bool) $service->get('security.allow_public_registration', false),
                'security_session_timeout_minutes' => (int) $service->get('security.session_timeout_minutes', 60),
                'security_max_login_attempts' => (int) $service->get('security.max_login_attempts', 5),
                'features_quiz_module' => (bool) $service->get('features.quiz_module', true),
                'features_parent_portal' => (bool) $service->get('features.parent_portal', true),
                'system_maintenance_mode' => (bool) $service->get('system.maintenance_mode', false),
            ],
            'providers' => $providers,
            'usage' => [
                'totalTokensToday' => $todayLogs->sum('total_tokens'),
                'totalCostToday' => $todayLogs->sum('cost_estimate_usd'),
                'totalCallsToday' => $todayLogs->count(),
                'failoverCallsToday' => $todayLogs->where('status', 'failover')->count(),
            ],
            'featureModelOptions' => AiVendorProviderCatalog::allCatalogModelIds(),
            'featureRecs' => $recs,
            'urls' => [
                'index' => route('settings.index'),
                'save' => route('settings.save'),
                'providersStore' => route('settings.providers.store'),
                'providersUpdate' => route('settings.providers.update', ['provider' => '__ID__']),
                'providersDestroy' => route('settings.providers.destroy', ['provider' => '__ID__']),
                'providersToggle' => route('settings.providers.toggle', ['provider' => '__ID__']),
                'providersPriority' => route('settings.providers.priority', ['provider' => '__ID__']),
                'providersTest' => route('settings.providers.test'),
            ],
        ]);
    }

    public function save(Request $request, SettingService $service): RedirectResponse
    {
        abort_unless(auth()->user()?->can(PermissionCatalog::SETTINGS_MANAGE), 403);

        $data = $request->validate([
            'ai_provider' => 'required|string|max:50',
            'ai_daily_limit_per_teacher' => 'required|integer|min:1|max:1000',
            'ai_anonymize_student_data' => 'required|boolean',
            'ai_model_plan' => 'required|string|max:100',
            'ai_model_material' => 'required|string|max:100',
            'ai_model_improve' => 'required|string|max:100',
            'ai_model_quiz' => 'required|string|max:100',
            'security_allow_public_registration' => 'required|boolean',
            'security_session_timeout_minutes' => 'required|integer|min:5|max:1440',
            'security_max_login_attempts' => 'required|integer|min:1|max:50',
            'features_quiz_module' => 'required|boolean',
            'features_parent_portal' => 'required|boolean',
            'system_maintenance_mode' => 'required|boolean',
        ]);

        $service->set('ai.provider', $data['ai_provider'], 'string', 'ai', 'Provider AI Utama');
        $service->set('ai.daily_limit_per_teacher', $data['ai_daily_limit_per_teacher'], 'integer', 'ai', 'Batas Generasi AI per Guru');
        $service->set('ai.anonymize_student_data', $data['ai_anonymize_student_data'], 'boolean', 'ai', 'Anonimisasi Data Siswa');
        $service->set('ai.model_plan', $data['ai_model_plan'], 'string', 'ai', 'Model Rekomendasi: Rencana Pembelajaran');
        $service->set('ai.model_material', $data['ai_model_material'], 'string', 'ai', 'Model Rekomendasi: Bahan Ajar / Co-Pilot');
        $service->set('ai.model_improve', $data['ai_model_improve'], 'string', 'ai', 'Model Rekomendasi: Perbaiki Teks');
        $service->set('ai.model_quiz', $data['ai_model_quiz'], 'string', 'ai', 'Model Rekomendasi: Soal / Kuis');

        $service->set('security.allow_public_registration', $data['security_allow_public_registration'], 'boolean', 'security', 'Pendaftaran Publik');
        $service->set('security.session_timeout_minutes', $data['security_session_timeout_minutes'], 'integer', 'security', 'Durasi Sesi Inaktif');
        $service->set('security.max_login_attempts', $data['security_max_login_attempts'], 'integer', 'security', 'Batas Percobaan Login');

        $service->set('features.quiz_module', $data['features_quiz_module'], 'boolean', 'features', 'Modul Kuis Online');
        $service->set('features.parent_portal', $data['features_parent_portal'], 'boolean', 'features', 'Portal Akses Wali Murid');
        $service->set('system.maintenance_mode', $data['system_maintenance_mode'], 'boolean', 'features', 'Mode Pemeliharaan Sistem');

        return back()->with('message', 'Pengaturan sistem global berhasil disimpan!');
    }

    public function storeProvider(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can(PermissionCatalog::SETTINGS_MANAGE), 403);

        $data = $this->validateProvider($request);

        $maxPriority = AiProvider::max('priority_order') ?? 0;
        AiProvider::create([
            'vendor_key' => $data['vendor_key'] ?: 'custom_'.time(),
            'name' => $data['name'],
            'is_active' => $data['is_active'],
            'priority_order' => $maxPriority + 1,
            'api_key' => $data['api_key'] ?: null,
            'base_url' => $data['base_url'] ?: null,
            'model' => $data['model'],
            'max_tokens' => $data['max_tokens'],
            'temperature' => $data['temperature'],
            'timeout_seconds' => $data['timeout'],
            'is_custom' => true,
        ]);

        return back()->with('message', 'Parameter vendor AI berhasil disimpan ke database!');
    }

    public function updateProvider(Request $request, AiProvider $provider): RedirectResponse
    {
        abort_unless(auth()->user()?->can(PermissionCatalog::SETTINGS_MANAGE), 403);

        $data = $this->validateProvider($request);

        $provider->update([
            'name' => $data['name'],
            'is_active' => $data['is_active'],
            'api_key' => $data['api_key'] ?: null,
            'base_url' => $data['base_url'] ?: null,
            'model' => $data['model'],
            'max_tokens' => $data['max_tokens'],
            'temperature' => $data['temperature'],
            'timeout_seconds' => $data['timeout'],
        ]);

        return back()->with('message', 'Parameter vendor AI berhasil disimpan ke database!');
    }

    public function destroyProvider(AiProvider $provider): RedirectResponse
    {
        abort_unless(auth()->user()?->can(PermissionCatalog::SETTINGS_MANAGE), 403);

        if ($provider->is_custom) {
            $provider->delete();
            $this->resequencePriorityOrders();

            return back()->with('message', 'Vendor AI kustom berhasil dihapus.');
        }

        return back()->with('error', 'Vendor bawaan tidak dapat dihapus.');
    }

    public function toggleProvider(AiProvider $provider): RedirectResponse
    {
        abort_unless(auth()->user()?->can(PermissionCatalog::SETTINGS_MANAGE), 403);

        $provider->update(['is_active' => ! $provider->is_active]);

        return back()->with(
            'message',
            "Status vendor {$provider->name} diperbarui menjadi ".($provider->is_active ? 'AKTIF' : 'NONAKTIF').'.'
        );
    }

    public function movePriority(Request $request, AiProvider $provider): RedirectResponse
    {
        abort_unless(auth()->user()?->can(PermissionCatalog::SETTINGS_MANAGE), 403);

        $direction = $request->validate(['direction' => 'required|in:up,down'])['direction'];
        $targetOrder = $direction === 'up' ? $provider->priority_order - 1 : $provider->priority_order + 1;
        $otherProvider = AiProvider::where('priority_order', $targetOrder)->first();

        if ($otherProvider) {
            $otherProvider->update(['priority_order' => $provider->priority_order]);
            $provider->update(['priority_order' => $targetOrder]);
            $this->resequencePriorityOrders();

            return back()->with(
                'message',
                "Prioritas failover {$provider->name} diubah ke peringkat #{$targetOrder}."
            );
        }

        return back();
    }

    public function testConnection(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can(PermissionCatalog::SETTINGS_MANAGE), 403);

        $data = $request->validate([
            'vendor_key' => 'required|string|max:100',
            'name' => 'nullable|string|max:100',
            'api_key' => 'nullable|string',
            'base_url' => 'nullable|string',
            'timeout' => 'nullable|integer|min:5|max:120',
        ]);

        $vendorKey = $data['vendor_key'];
        $name = $data['name'] ?: $vendorKey;
        $apiKey = $data['api_key'] ?? '';
        $baseUrl = $data['base_url'] ?? '';
        $timeout = $data['timeout'] ?? 30;

        if ($vendorKey === 'mock') {
            return response()->json([
                'type' => 'success',
                'message' => 'Koneksi Mock Mode Aktif — Simulasi offline siap digunakan.',
            ]);
        }

        $meta = AiVendorProviderCatalog::get($vendorKey);
        if ($meta && ($meta['requires_key'] ?? false) && empty(trim($apiKey))) {
            return response()->json([
                'type' => 'danger',
                'message' => "API Key untuk vendor {$name} belum diisi.",
            ]);
        }

        try {
            if ($vendorKey === 'gemini') {
                $url = rtrim($baseUrl, '/')."/models?key={$apiKey}";
                $response = Http::timeout($timeout)->get($url);
            } else {
                $url = rtrim($baseUrl, '/').'/models';
                $req = Http::timeout($timeout);
                if (! empty($apiKey)) {
                    $req->withToken($apiKey);
                }
                $response = $req->get($url);
            }

            if ($response->successful()) {
                return response()->json([
                    'type' => 'success',
                    'message' => "Terhubung! API Endpoint {$name} merespons dengan sukses (HTTP {$response->status()}).",
                ]);
            }

            return response()->json([
                'type' => 'danger',
                'message' => "Gagal terhubung ke {$name} (HTTP {$response->status()}). Periksa API Key dan Base URL.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'danger',
                'message' => 'Gagal terhubung: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProvider(Request $request): array
    {
        return $request->validate([
            'vendor_key' => 'nullable|string|max:100',
            'name' => 'required|string|max:100',
            'is_active' => 'required|boolean',
            'api_key' => 'nullable|string',
            'base_url' => 'nullable|string|max:255',
            'model' => 'required|string|max:100',
            'max_tokens' => 'required|integer|min:256|max:16384',
            'temperature' => 'required|numeric|min:0|max:1',
            'timeout' => 'required|integer|min:5|max:120',
        ]);
    }

    private function resequencePriorityOrders(): void
    {
        $providers = AiProvider::orderBy('priority_order', 'asc')->get();
        $order = 1;
        foreach ($providers as $p) {
            $p->update(['priority_order' => $order++]);
        }
    }
}
