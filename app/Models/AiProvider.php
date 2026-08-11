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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    use HasFactory;

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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority_order', 'asc');
    }

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
