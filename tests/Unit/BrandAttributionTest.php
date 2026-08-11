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

use App\Support\BrandAttribution;
use Tests\TestCase;

class BrandAttributionTest extends TestCase
{
    public function test_inertia_payload_contains_canonical_brand(): void
    {
        $brand = BrandAttribution::forInertia();

        $this->assertSame('Aksara', $brand['product']);
        $this->assertSame('jejakawan', $brand['owner']);
        $this->assertSame('https://jejakawan.com', $brand['ownerUrl']);
        $this->assertSame('MIT', $brand['license']);
        $this->assertStringContainsString('Aksara', $brand['line']);
        $this->assertStringContainsString('jejakawan', $brand['line']);
        $this->assertArrayHasKey('faviconUrl', $brand);
        $this->assertStringContainsString('brand/jejakawan/favicon.ico', $brand['faviconUrl']);
        $this->assertFalse($brand['usingCustomFavicon']);
    }

    public function test_jejakawan_favicon_and_logo_exist_in_public(): void
    {
        $this->assertFileExists(public_path(BrandAttribution::OWNER_FAVICON_PATH));
        $this->assertFileExists(public_path(BrandAttribution::OWNER_LOGO_PATH));
        $this->assertGreaterThan(0, filesize(public_path(BrandAttribution::OWNER_FAVICON_PATH)));
    }

    public function test_brand_copyright_vue_component_exists_with_markers(): void
    {
        $path = resource_path('js/Components/brand/BrandCopyright.vue');
        $this->assertFileExists($path);
        $contents = (string) file_get_contents($path);
        $this->assertStringContainsString('Aksara', $contents);
        $this->assertStringContainsString('jejakawan', $contents);
        $this->assertStringContainsString('https://jejakawan.com', $contents);
    }
}
