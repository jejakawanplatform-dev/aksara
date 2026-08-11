<?php

/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 *
 * Canonical product attribution. UI footers and agents must use these values.
 * Cryptographic "locks" on Vue footers are security theater — use LICENSE + process instead.
 */

namespace App\Support;

final class BrandAttribution
{
    public const PRODUCT = 'Aksara';

    public const TAGLINE = 'Pembelajaran AI';

    public const OWNER = 'jejakawan';

    public const OWNER_URL = 'https://jejakawan.com';

    public const LICENSE = 'MIT';

    public const COPYRIGHT_YEAR = 2026;

    /** Fallback jejakawan assets (bukan logo produk Aksara). */
    public const OWNER_LOGO_PATH = 'brand/jejakawan/logo.png';

    public const OWNER_FAVICON_PATH = 'brand/jejakawan/favicon.ico';

    public const OWNER_APPLE_TOUCH_PATH = 'brand/jejakawan/apple-touch-icon.png';

    /** Override sekolah/instansi — taruh file di public/brand/custom/ */
    public const CUSTOM_FAVICON_PATH = 'brand/custom/favicon.ico';

    public const CUSTOM_LOGO_PATH = 'brand/custom/logo.png';

    public const CUSTOM_APPLE_TOUCH_PATH = 'brand/custom/apple-touch-icon.png';

    /**
     * @return array{
     *     product: string,
     *     tagline: string,
     *     owner: string,
     *     ownerUrl: string,
     *     license: string,
     *     year: int,
     *     line: string,
     *     shortLine: string,
     *     faviconUrl: string,
     *     logoUrl: string,
     *     appleTouchUrl: string,
     *     usingCustomFavicon: bool
     * }
     */
    public static function forInertia(): array
    {
        $year = self::COPYRIGHT_YEAR;
        $assets = self::assetUrls();

        return [
            'product' => self::PRODUCT,
            'tagline' => self::TAGLINE,
            'owner' => self::OWNER,
            'ownerUrl' => self::OWNER_URL,
            'license' => self::LICENSE,
            'year' => $year,
            'line' => "© {$year} ".self::PRODUCT.' · '.self::OWNER.' · '.self::LICENSE,
            'shortLine' => "© {$year} ".self::PRODUCT,
            'faviconUrl' => $assets['faviconUrl'],
            'logoUrl' => $assets['logoUrl'],
            'appleTouchUrl' => $assets['appleTouchUrl'],
            'usingCustomFavicon' => $assets['usingCustomFavicon'],
        ];
    }

    /**
     * Favicon/logo: custom sekolah jika ada, else fallback jejakawan.
     *
     * @return array{faviconUrl: string, logoUrl: string, appleTouchUrl: string, usingCustomFavicon: bool}
     */
    public static function assetUrls(): array
    {
        $customFavicon = public_path(self::CUSTOM_FAVICON_PATH);
        $usingCustom = is_file($customFavicon) && filesize($customFavicon) > 0;

        $faviconRel = $usingCustom ? self::CUSTOM_FAVICON_PATH : self::OWNER_FAVICON_PATH;
        $logoRel = (is_file(public_path(self::CUSTOM_LOGO_PATH)) && filesize(public_path(self::CUSTOM_LOGO_PATH)) > 0)
            ? self::CUSTOM_LOGO_PATH
            : self::OWNER_LOGO_PATH;
        $appleRel = (is_file(public_path(self::CUSTOM_APPLE_TOUCH_PATH)) && filesize(public_path(self::CUSTOM_APPLE_TOUCH_PATH)) > 0)
            ? self::CUSTOM_APPLE_TOUCH_PATH
            : self::OWNER_APPLE_TOUCH_PATH;

        return [
            'faviconUrl' => asset($faviconRel),
            'logoUrl' => asset($logoRel),
            'appleTouchUrl' => asset($appleRel),
            'usingCustomFavicon' => $usingCustom,
        ];
    }

    /**
     * Soft integrity: ensure BrandCopyright.vue still contains required attribution markers.
     * Logs a warning only — does not crash the app (forks remain runnable under MIT).
     */
    public static function assertUiComponentIntact(): void
    {
        $path = resource_path('js/Components/brand/BrandCopyright.vue');
        if (! is_file($path)) {
            logger()->warning('Aksara brand: BrandCopyright.vue missing. Restore attribution UI per NOTICE / ADR-013.');

            return;
        }

        $contents = (string) file_get_contents($path);
        foreach ([self::PRODUCT, self::OWNER, self::OWNER_URL, '@license'] as $needle) {
            if (! str_contains($contents, $needle)) {
                logger()->warning('Aksara brand: BrandCopyright.vue looks altered (missing "'.$needle.'"). See NOTICE and docs/steering/file-header.md.');

                return;
            }
        }

        if (! is_file(public_path(self::OWNER_FAVICON_PATH))) {
            logger()->warning('Aksara brand: jejakawan favicon missing at public/'.self::OWNER_FAVICON_PATH);
        }
    }
}
