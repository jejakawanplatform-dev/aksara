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

namespace App\Models;

use App\Support\Ai\AiVendorProviderCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type VendorCatalogMeta from \App\Support\Ai\AiVendorProviderCatalog
 *
 * @property int $id
 * @property string $vendor_key
 * @property string $name
 * @property bool $is_active
 * @property int $priority_order
 * @property string|null $api_key
 * @property string|null $base_url
 * @property string|null $model
 * @property int|null $max_tokens
 * @property float|null $temperature
 * @property int|null $timeout_seconds
 * @property array<string, mixed>|null $custom_headers
 * @property bool $is_custom
 */
class AiProvider extends Model
{
    protected $fillable = [
        'vendor_key',
        'name',
        'is_active',
        'priority_order',
        'api_key',
        'base_url',
        'model',
        'max_tokens',
        'temperature',
        'timeout_seconds',
        'custom_headers',
        'is_custom',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_custom' => 'boolean',
            'priority_order' => 'integer',
            'max_tokens' => 'integer',
            'temperature' => 'float',
            'timeout_seconds' => 'integer',
            'custom_headers' => 'array',
        ];
    }

    /**
     * @param  Builder<AiProvider>  $query
     * @return Builder<AiProvider>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<AiProvider>  $query
     * @return Builder<AiProvider>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority_order', 'asc');
    }

    /**
     * @return VendorCatalogMeta|null
     */
    public function catalogMeta(): ?array
    {
        return AiVendorProviderCatalog::get($this->vendor_key);
    }

    public function isConfigured(): bool
    {
        if ($this->vendor_key === 'mock' || $this->vendor_key === 'ollama') {
            return true;
        }

        return ! empty(trim($this->api_key ?? ''));
    }

    public function supportsImageGeneration(): bool
    {
        return (bool) ($this->catalogMeta()['supports_image_generation'] ?? false);
    }

    /**
     * True when at least one active, configured provider can generate images (OpenAI / Gemini).
     */
    public static function hasConfiguredImageGeneration(): bool
    {
        return static::active()
            ->ordered()
            ->get()
            ->contains(fn (self $provider) => $provider->supportsImageGeneration() && $provider->isConfigured());
    }
}
