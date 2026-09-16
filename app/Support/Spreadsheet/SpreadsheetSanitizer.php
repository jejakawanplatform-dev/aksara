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

namespace App\Support\Spreadsheet;

class SpreadsheetSanitizer
{
    /**
     * Karakter berbahaya di awal nilai sel yang dapat dieksekusi sebagai rumus/formula.
     *
     * @var list<string>
     */
    private const DANGEROUS_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Sanitasi nilai sel untuk mencegah spreadsheet formula injection.
     * Jika string diawali dengan karakter berbahaya, tambahkan tanda petik tunggal (') di awal.
     */
    public static function sanitize(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = is_scalar($value) ? (string) $value : '';
        $trimmed = ltrim($str, ' ');

        if ($trimmed !== '') {
            $firstChar = $trimmed[0];
            if (in_array($firstChar, self::DANGEROUS_PREFIXES, true)) {
                return "'".$str;
            }
        }

        return $str;
    }
}
