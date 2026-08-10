<?php

namespace App\Support;

use App\Models\Subject;

final class SubjectContext
{
    /**
     * Kode mapel STEM yang membutuhkan dukungan rumus Matematika / Notasi IPA / Sains.
     */
    public const STEM_CODES = ['MTK', 'IPA', 'INF', 'FIS', 'KIM', 'BIO'];

    /**
     * Kata kunci nama mapel STEM.
     */
    public const STEM_KEYWORDS = [
        'matematika',
        'ipa',
        'ilmu pengetahuan alam',
        'informatika',
        'fisika',
        'kimia',
        'biologi',
        'sains',
        'koding',
        'coding',
    ];

    /**
     * Memeriksa apakah mapel tergolong STEM / Math-enabled.
     */
    public static function isStem(?Subject $subject = null, ?string $code = null, ?string $name = null): bool
    {
        if ($subject) {
            $code = $subject->code ?? '';
            $name = $subject->name ?? '';
        }

        if ($code && in_array(strtoupper(trim($code)), self::STEM_CODES, true)) {
            return true;
        }

        if ($name) {
            $lowerName = strtolower(trim($name));
            foreach (self::STEM_KEYWORDS as $keyword) {
                if (str_contains($lowerName, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }
}
