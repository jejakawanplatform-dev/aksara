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

namespace App\Enums;

enum PlanStatus: string
{
    case Draft = 'draft';
    case Reviewed = 'reviewed';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Reviewed => 'Sudah Direview',
            self::Published => 'Diterbitkan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'badge-warning',
            self::Reviewed => 'badge-info',
            self::Published => 'badge-success',
        };
    }
}
