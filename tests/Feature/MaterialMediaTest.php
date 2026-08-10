<?php

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Models\User;
use App\Services\MaterialImageService;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
        $this->seed(SystemSettingSeeder::class);
        Storage::fake('public');
    }

    public function test_guru_dapat_list_upload_dan_hapus_media_konteks_materi(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $material = LearningMaterial::where('plan_id', $plan->id)->firstOrFail();

        $this->actingAs($guru)->getJson(route('materials.media', $material))
            ->assertOk()
            ->assertJsonPath('items', []);

        $png = base64_encode(hex2bin(
            '89504e470d0a1a0a0000000d49484452000000010000000108060000001f15c4890000000a49444154789c63000100000500010d0a2db40000000049454e44ae426082'
        ));

        $upload = $this->actingAs($guru)->postJson(route('materials.images', $material), [
            'dataUrl' => 'data:image/png;base64,'.$png,
            'originalName' => 'dot.png',
        ]);

        $upload->assertOk();
        $url = $upload->json('url');
        $this->assertNotEmpty($url);
        $this->assertStringStartsWith('/storage/materials/'.$material->id.'/', $url);

        $relative = ltrim(str_replace('/storage/', '', $url), '/');
        Storage::disk('public')->assertExists($relative);

        $list = $this->actingAs($guru)->getJson(route('materials.media', $material));
        $list->assertOk();
        $list->assertJsonCount(1, 'items');
        $name = $list->json('items.0.name');
        $this->assertSame($url, $list->json('items.0.url'));

        $this->actingAs($guru)
            ->deleteJson(route('materials.media.destroy', [$material, $name]))
            ->assertOk()
            ->assertJsonPath('ok', true);

        Storage::disk('public')->assertMissing($relative);
        $this->actingAs($guru)->getJson(route('materials.media', $material))
            ->assertOk()
            ->assertJsonPath('items', []);
    }

    public function test_media_tidak_bocor_antar_materi_dan_path_traversal_ditolak(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $materialA = LearningMaterial::where('plan_id', $plan->id)->firstOrFail();

        /** @var MaterialImageService $images */
        $images = app(MaterialImageService::class);
        $png = hex2bin(
            '89504e470d0a1a0a0000000d49484452000000010000000108060000001f15c4890000000a49444154789c63000100000500010d0a2db40000000049454e44ae426082'
        );
        $this->assertNotFalse($png);

        $urlA = $images->storeBinary($materialA, $png, 'png');
        $nameA = basename(str_replace('/storage/', '', $urlA));

        $otherMaterial = LearningMaterial::query()
            ->where('id', '!=', $materialA->id)
            ->whereHas('plan', fn ($q) => $q->where('teacher_id', $guru->id))
            ->first();

        if ($otherMaterial) {
            $listOther = $this->actingAs($guru)->getJson(route('materials.media', $otherMaterial));
            $listOther->assertOk();
            $urls = collect($listOther->json('items') ?? [])->pluck('url')->all();
            $this->assertNotContains($urlA, $urls);

            $this->actingAs($guru)
                ->deleteJson(route('materials.media.destroy', [$otherMaterial, $nameA]))
                ->assertStatus(422);
        }

        $this->actingAs($guru)
            ->deleteJson('/materials/'.$materialA->id.'/media/'.rawurlencode('../'.$nameA))
            ->assertStatus(404);

        $this->actingAs($guru)
            ->deleteJson(route('materials.media.destroy', [$materialA, 'not-an-image.exe']))
            ->assertStatus(422);

        Storage::disk('public')->assertExists('materials/'.$materialA->id.'/'.$nameA);
    }

    public function test_edit_page_menyediakan_endpoint_media(): void
    {
        $guru = User::where('email', 'naya@aksara.test')->firstOrFail();
        $plan = LearningPlan::where('teacher_id', $guru->id)->firstOrFail();
        $material = LearningMaterial::where('plan_id', $plan->id)->firstOrFail();

        $this->actingAs($guru)->get("/materials/{$material->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Materials/Edit')
                ->has('endpoints.media')
                ->has('endpoints.mediaDestroyBase')
                ->has('endpoints.images')
                ->has('endpoints.copilot')
                ->where('isStem', true)
                ->has('form.sections')
            );
    }

    public function test_siswa_tidak_bisa_akses_api_media_materi(): void
    {
        $siswa = User::where('email', 'adit@aksara.test')->firstOrFail();
        $material = LearningMaterial::query()->firstOrFail();

        $this->actingAs($siswa)
            ->getJson(route('materials.media', $material))
            ->assertForbidden();
    }
}
