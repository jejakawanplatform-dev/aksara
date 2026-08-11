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

namespace Tests\Unit;

use App\Models\AiProvider;
use App\Services\AiDraftService;
use App\Support\Ai\AiVendorProviderCatalog;
use Database\Seeders\AiProviderSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiModelResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AiProviderSeeder::class);
        $this->seed(SystemSettingSeeder::class);
    }

    public function test_resolve_model_for_material_uses_feature_setting_when_in_catalog(): void
    {
        setting()->set('ai.model_material', 'llama-3.1-8b-instant', 'string', 'ai', 'Model Bahan Ajar');

        $groq = AiProvider::where('vendor_key', 'groq')->firstOrFail();
        $service = app(AiDraftService::class);

        $this->assertSame(
            'llama-3.1-8b-instant',
            $service->resolveModelFor(AiDraftService::FEATURE_MATERIAL, $groq)
        );
    }

    public function test_resolve_model_falls_back_to_provider_when_feature_model_not_in_catalog(): void
    {
        setting()->set('ai.model_material', 'llama-3.3-70b-versatile', 'string', 'ai', 'Model Bahan Ajar');

        $openai = AiProvider::where('vendor_key', 'openai')->firstOrFail();
        $openai->update(['model' => 'gpt-4o-mini']);
        $service = app(AiDraftService::class);

        $this->assertSame(
            'gpt-4o-mini',
            $service->resolveModelFor(AiDraftService::FEATURE_MATERIAL, $openai)
        );
    }

    public function test_feature_model_recommendations_catalog_keys_exist(): void
    {
        $recs = AiVendorProviderCatalog::featureModelRecommendations();
        $this->assertArrayHasKey('plan', $recs);
        $this->assertArrayHasKey('material', $recs);
        $this->assertFalse($recs['quiz']['enabled']);
        $this->assertNotEmpty(AiVendorProviderCatalog::allCatalogModelIds());
        $this->assertNotEmpty(AiVendorProviderCatalog::guideForModel('llama-3.3-70b-versatile')['recommend']);
    }

    public function test_preferred_model_overrides_feature_setting_when_in_catalog(): void
    {
        setting()->set('ai.model_material', 'llama-3.3-70b-versatile', 'string', 'ai', 'Model Bahan Ajar');

        $groq = AiProvider::where('vendor_key', 'groq')->firstOrFail();
        $service = app(AiDraftService::class);

        $this->assertSame(
            'llama-3.1-8b-instant',
            $service->resolveModelFor(AiDraftService::FEATURE_MATERIAL, $groq, null, 'llama-3.1-8b-instant')
        );
    }

    public function test_list_material_model_choices_from_active_providers(): void
    {
        AiProvider::query()->update(['is_active' => false]);
        AiProvider::where('vendor_key', 'groq')->update([
            'is_active' => true,
            'api_key' => 'gsk_test',
        ]);

        $choices = app(AiDraftService::class)->listMaterialModelChoices();
        $ids = collect($choices)->pluck('id')->all();

        $this->assertContains('llama-3.3-70b-versatile', $ids);
        $this->assertArrayHasKey('recommend', $choices[0]);
        $this->assertArrayHasKey('limit', $choices[0]);
    }

    public function test_model_ids_from_providers_only_includes_active_vendor_catalog(): void
    {
        AiProvider::query()->update(['is_active' => false]);
        $groq = AiProvider::where('vendor_key', 'groq')->firstOrFail();
        $groq->update(['is_active' => true, 'api_key' => 'gsk_test']);

        $ids = AiVendorProviderCatalog::modelIdsFromProviders(AiProvider::active()->get());

        $this->assertContains('llama-3.3-70b-versatile', $ids);
        $this->assertNotContains('gpt-4o', $ids);
        $this->assertNotContains('gemini-1.5-flash', $ids);
    }
}
