<?php

namespace App\Support;

/**
 * Normalizes material section HTML so TipTap never receives broken AI-hallucinated images,
 * and plain-text illustration tips are upgraded to readable HTML blocks.
 *
 * Illustration "saran + Prompt AI Image" blocks are teacher authoring aids.
 * Use forStudent() when rendering materi untuk siswa.
 */
final class MaterialContentHtml
{
    /**
     * Sanitize + normalize one section body for TipTap.
     * Idempotent: running twice must not nest or duplicate illustration blocks.
     */
    public static function sanitizeSectionBody(string $html): string
    {
        $html = self::stripUntrustedImages($html);
        $html = self::normalizeIllustrationSuggestions($html);
        $html = self::ensureIllustrationPrompts($html);

        return $html;
    }

    /**
     * Strip teacher-only illustration tips (saran + prompt + link unduh) before siswa sees content.
     * Keeps real uploaded images and normal blockquotes.
     */
    public static function forStudent(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $html = self::mapBalancedBlockquotes($html, static function (string $full, string $inner): string {
            if (preg_match('/Saran\s+Ilustrasi|🎨\s*Ilustrasi:|Prompt\s+AI\s+Image|Cari\s*&(?:amp;)?\s*unduh\s+di\s+Unsplash/iu', $inner)) {
                return '';
            }

            return $full;
        });

        // Plain-text leftovers from older AI output.
        $html = (string) preg_replace('/<p[^>]*>\s*Saran\s+ilustrasi\s*:.*?<\/p>/isu', '', $html);
        $html = (string) preg_replace('/(?<![\w;])Saran\s+ilustrasi\s*:[^\n<]*/iu', '', $html);

        return trim($html);
    }

    /**
     * Replace untrusted <img> tags with a TipTap-safe illustration blockquote.
     */
    public static function stripUntrustedImages(string $html): string
    {
        if ($html === '' || ! str_contains(strtolower($html), '<img')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<img\b([^>]*)>/i',
            static function (array $matches): string {
                $attrs = $matches[1];
                $src = self::attributeValue($attrs, 'src');
                $alt = self::attributeValue($attrs, 'alt') ?: 'Ilustrasi materi';

                if (self::isTrustedImageSrc($src)) {
                    return $matches[0];
                }

                return self::illustrationBlockHtml($alt, null, null, null);
            },
            $html
        );
    }

    /**
     * Convert AI plain-text illustration tips into structured HTML blockquotes.
     * Never re-wraps already-normalized emoji/HTML blocks (prevents nested loop).
     */
    public static function normalizeIllustrationSuggestions(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $html = self::flattenIllustrationBlockquotes($html);

        $pattern = '/(?:<p[^>]*>)\s*Saran\s+ilustrasi\s*:\s*(.*?)<\/p>/isu';

        $html = (string) preg_replace_callback($pattern, static function (array $m): string {
            $blob = html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $blob = trim(preg_replace('/\s+/u', ' ', $blob) ?? $blob);

            if ($blob === '') {
                return $m[0];
            }

            return self::parsePlainIllustrationBlob($blob);
        }, $html);

        if (preg_match('/Saran\s+ilustrasi\s*:/iu', $html)
            && ! preg_match('/<p[^>]*>\s*Saran\s+ilustrasi\s*:/iu', $html)) {
            $html = (string) preg_replace_callback(
                '/(?<![\w;])Saran\s+ilustrasi\s*:\s*([^\n<]+)/iu',
                static function (array $m): string {
                    if (str_contains($m[0], '🖼️') || str_contains($m[0], '🎨')) {
                        return $m[0];
                    }

                    $blob = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if ($blob === '') {
                        return $m[0];
                    }

                    return self::parsePlainIllustrationBlob($blob);
                },
                $html
            );
        }

        return $html;
    }

    /**
     * Ensure every illustration tip block has a copyable Prompt AI Image line.
     */
    public static function ensureIllustrationPrompts(string $html): string
    {
        if ($html === '' || ! preg_match('/Saran\s+Ilustrasi|🎨\s*Ilustrasi:/u', $html)) {
            return $html;
        }

        return self::mapBalancedBlockquotes($html, static function (string $full, string $inner): string {
            if (! preg_match('/Saran\s+Ilustrasi|🎨\s*Ilustrasi:/u', $inner)) {
                return $full;
            }

            if (preg_match('/Prompt\s+AI\s+Image/iu', $inner)
                && preg_match('/<code\b/i', $inner)) {
                return $full;
            }

            $plain = strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $inner));

            return self::rebuildIllustrationFromBlob($plain);
        });
    }

    /**
     * Collapse nested <blockquote> trees that contain saran ilustrasi into a single block.
     */
    public static function flattenIllustrationBlockquotes(string $html): string
    {
        if ($html === '' || ! str_contains(strtolower($html), 'blockquote')) {
            return $html;
        }

        if (! preg_match('/Saran\s+Ilustrasi|Ilustrasi:/iu', $html)) {
            return $html;
        }

        if (! preg_match('/Saran\s+Ilustrasi[\s\S]*?<blockquote/iu', $html)
            && preg_match_all('/Cari\s*&(?:amp;)?\s*unduh\s+di\s+Unsplash/iu', $html) <= 1) {
            return $html;
        }

        return self::mapBalancedBlockquotes($html, static function (string $full, string $inner): string {
            if (preg_match('/Saran\s+Ilustrasi|🎨\s*Ilustrasi:/iu', $inner)
                && (str_contains(strtolower($inner), '<blockquote')
                    || preg_match_all('/Cari\s*&(?:amp;)?\s*unduh\s+di\s+Unsplash/iu', $inner) > 1
                    || preg_match_all('/Sumber\s*:/iu', $inner) > 1)) {
                $plain = strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $inner));

                return self::rebuildIllustrationFromBlob($plain);
            }

            return $full;
        });
    }

    /**
     * @param  callable(string $full, string $inner): string  $mapper
     */
    private static function mapBalancedBlockquotes(string $html, callable $mapper): string
    {
        $out = '';
        $offset = 0;
        $length = strlen($html);

        while ($offset < $length) {
            if (! preg_match('/<blockquote\b[^>]*>/i', $html, $openMatch, PREG_OFFSET_CAPTURE, $offset)) {
                $out .= substr($html, $offset);
                break;
            }

            $openPos = (int) $openMatch[0][1];
            $openLen = strlen($openMatch[0][0]);
            $out .= substr($html, $offset, $openPos - $offset);

            $depth = 1;
            $cursor = $openPos + $openLen;
            $endPos = null;
            $closeTagLen = 0;

            while ($cursor < $length) {
                if (! preg_match('/<\/?blockquote\b[^>]*>/i', $html, $tagMatch, PREG_OFFSET_CAPTURE, $cursor)) {
                    break;
                }

                $tag = $tagMatch[0][0];
                $tagPos = (int) $tagMatch[0][1];
                $tagLen = strlen($tag);

                if (str_starts_with(strtolower($tag), '</')) {
                    $depth--;
                    if ($depth === 0) {
                        $endPos = $tagPos + $tagLen;
                        $closeTagLen = $tagLen;
                        break;
                    }
                } else {
                    $depth++;
                }

                $cursor = $tagPos + $tagLen;
            }

            if ($endPos === null) {
                $out .= substr($html, $openPos);
                break;
            }

            $full = substr($html, $openPos, $endPos - $openPos);
            $inner = substr($html, $openPos + $openLen, $endPos - $openPos - $openLen - $closeTagLen);
            $out .= $mapper($full, $inner);
            $offset = $endPos;
        }

        return $out;
    }

    private static function parsePlainIllustrationBlob(string $blob): string
    {
        $desc = null;
        $prompt = null;
        $unsplash = null;
        $commons = null;
        $source = null;

        if (preg_match('/ID\s*:\s*([^,]+)/iu', $blob, $mm)) {
            $desc = trim($mm[1]);
        }

        if (preg_match('/(?:🎯\s*)?Prompt\s+AI\s+Image(?:\s+EN)?\s*:\s*(.+?)(?=,\s*Link\s|\s+Link\s|,?\s*Sumber\s*:|Cari\s|&\s*unduh|Wikimedia|$)/iu', $blob, $mm)) {
            $prompt = trim($mm[1], " \t\n\r\0\x0B,;");
            $prompt = preg_replace('/^(?:<em>|<\/em>|<code>|<\/code>)+/iu', '', $prompt) ?? $prompt;
            $prompt = trim($prompt, " \t\n\r\0\x0B,;");
        }

        if (preg_match('/Link\s+Unsplash\s*:\s*(https?:\/\/[^\s,]+)/iu', $blob, $mm)) {
            $unsplash = trim($mm[1]);
        }
        if (preg_match('/Link\s+(?:Wikimedia(?:\s+Commons)?)\s*:\s*(https?:\/\/[^\s,]+)/iu', $blob, $mm)) {
            $commons = trim($mm[1]);
        }
        if (preg_match('/https?:\/\/unsplash\.com\/[^\s,<"]+/iu', $blob, $mm) && $unsplash === null) {
            $unsplash = rtrim($mm[0], '.,);');
        }
        if (preg_match('/https?:\/\/commons\.wikimedia\.org\/[^\s,<"]+/iu', $blob, $mm) && $commons === null) {
            $commons = rtrim($mm[0], '.,);');
        }
        if (preg_match('/Sumber\s*:\s*(.+)$/iu', $blob, $mm)) {
            $source = trim($mm[1], " \t\n\r\0\x0B,;");
            $source = preg_replace('/\.?\s*Unduh atau generate.*$/iu', '', $source) ?? $source;
            $source = trim($source);
        }

        if ($desc === null && preg_match('/^(?:🖼️\s*Saran\s+Ilustrasi\s*:?\s*|🎨\s*Ilustrasi\s*:?\s*)?(.+?)(?=,\s*Prompt|,?\s*Link|,?\s*Sumber|🎯|Cari\s|Prompt\s+AI)/iu', $blob, $mm)) {
            $desc = trim($mm[1], " \t\n\r\0\x0B,:");
            $desc = preg_replace('/^(?:🖼️\s*Saran\s+Ilustrasi|🎨\s*Ilustrasi)\s*:?\s*/u', '', $desc) ?? $desc;
        }

        if ($desc === null || $desc === '') {
            $desc = preg_match('/Prompt|Link|Unsplash|Sumber|ID\s*:/i', $blob)
                ? 'Ilustrasi materi'
                : trim($blob);
        }

        $desc = $desc !== '' ? $desc : 'Ilustrasi materi';

        return self::illustrationBlockHtml($desc, $prompt, $unsplash, $commons, $source !== '' ? $source : null);
    }

    private static function rebuildIllustrationFromBlob(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
        $text = preg_replace('/^🖼️\s*Saran\s+Ilustrasi\s*:?\s*/u', '', $text) ?? $text;

        return self::parsePlainIllustrationBlob($text);
    }

    public static function defaultImagePrompt(string $description): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $description) ?? $description);

        return 'Educational textbook illustration, clear simple style, no text overlay: '.$clean;
    }

    public static function illustrationBlockHtml(
        string $description,
        ?string $promptEn = null,
        ?string $unsplashUrl = null,
        ?string $commonsUrl = null,
        ?string $sourceNote = null,
    ): string {
        $desc = e($description);
        $prompt = trim((string) $promptEn);
        if ($prompt === '') {
            $prompt = self::defaultImagePrompt($description);
        }

        $parts = [
            '<blockquote>',
            '<p><strong>🖼️ Saran Ilustrasi:</strong> '.$desc.'</p>',
            '<p><strong>🎯 Prompt AI Image:</strong> <code>'.e($prompt).'</code></p>',
        ];

        $links = [];
        if ($unsplashUrl) {
            $links[] = '<a href="'.e($unsplashUrl).'" target="_blank" rel="noopener noreferrer">Cari &amp; unduh di Unsplash</a>';
        } else {
            $slug = rawurlencode(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $description) ?? 'illustration'));
            $links[] = '<a href="https://unsplash.com/s/photos/'.$slug.'" target="_blank" rel="noopener noreferrer">Cari &amp; unduh di Unsplash</a>';
        }
        if ($commonsUrl) {
            $links[] = '<a href="'.e($commonsUrl).'" target="_blank" rel="noopener noreferrer">Cari di Wikimedia Commons</a>';
        } else {
            $q = rawurlencode($description);
            $links[] = '<a href="https://commons.wikimedia.org/w/index.php?search='.$q.'&amp;title=Special:MediaSearch&amp;type=image" target="_blank" rel="noopener noreferrer">Cari di Wikimedia Commons</a>';
        }
        $parts[] = '<p>'.implode(' · ', $links).'</p>';

        $source = $sourceNote ?: 'Unsplash / Wikimedia Commons (lisensi bebas)';
        if (preg_match('/Unduh atau generate/iu', $source)) {
            $parts[] = '<p><em>'.e($source).'</em></p>';
        } else {
            $parts[] = '<p><em>Sumber: '.e($source).'. Salin prompt di atas ke AI image generator, atau unduh dari tautan, lalu unggah lewat tombol Gambar di editor (hanya untuk guru).</em></p>';
        }
        $parts[] = '</blockquote>';

        return implode('', $parts);
    }

    /**
     * @param  array<int, array{heading?: string, body?: string}>  $sections
     * @return array<int, array{heading: string, body: string}>
     */
    public static function sanitizeSections(array $sections): array
    {
        $normalized = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $normalized[] = [
                'heading' => (string) ($section['heading'] ?? ''),
                'body' => self::sanitizeSectionBody((string) ($section['body'] ?? '')),
            ];
        }

        return $normalized;
    }

    public static function isTrustedImageSrc(string $src): bool
    {
        $src = trim($src);

        if ($src === '') {
            return false;
        }

        if (str_starts_with($src, 'data:image/')) {
            return true;
        }

        if (str_starts_with($src, '/storage/')) {
            return true;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && str_starts_with($src, $appUrl.'/storage/')) {
            return true;
        }

        return false;
    }

    private static function attributeValue(string $attrs, string $name): string
    {
        if (preg_match('/\b'.preg_quote($name, '/').'\s*=\s*(["\'])(.*?)\1/i', $attrs, $m)) {
            return html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (preg_match('/\b'.preg_quote($name, '/').'\s*=\s*([^\s>]+)/i', $attrs, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return '';
    }
}
