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

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    public const CACHE_KEY = 'aksara_system_settings_map';

    /**
     * Ambil nilai setting berdasarkan key (dengan cache).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->allCached();

        if (! array_key_exists($key, $settings)) {
            return $default;
        }

        return $settings[$key];
    }

    /**
     * Simpan / Perbarui nilai setting dan bersihkan cache.
     */
    public function set(string $key, mixed $value, ?string $type = null, ?string $group = null, ?string $label = null): void
    {
        $setting = SystemSetting::query()->firstOrNew(['key' => $key]);

        if (is_bool($value)) {
            $setting->value = $value ? '1' : '0';
            $setting->type = $type ?? 'boolean';
        } elseif (is_numeric($value)) {
            $setting->value = (string) $value;
            $setting->type = $type ?? (is_float($value) ? 'float' : 'integer');
        } elseif (is_array($value) || is_object($value)) {
            $setting->value = json_encode($value);
            $setting->type = $type ?? 'json';
        } else {
            $setting->value = (string) $value;
            $setting->type = $type ?? 'string';
        }

        if ($group) {
            $setting->group = $group;
        }
        if ($label) {
            $setting->label = $label;
        }

        $setting->save();

        $this->clearCache();
    }

    /**
     * Hapus cache setting.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Ambil seluruh peta setting terproses dari cache.
     *
     * @return array<string, mixed>
     */
    public function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $raw = SystemSetting::all();
            $map = [];

            foreach ($raw as $s) {
                $val = $s->value;
                $map[$s->key] = match ($s->type) {
                    'boolean' => filter_var($val, FILTER_VALIDATE_BOOLEAN),
                    'integer' => (int) $val,
                    'float' => (float) $val,
                    'json' => json_decode($val, true) ?? [],
                    default => $val,
                };
            }

            return $map;
        });
    }
}
