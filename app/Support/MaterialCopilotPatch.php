<?php

namespace App\Support;

/**
 * Merge Co-Pilot patch into existing editor sections (ADR-009).
 * Mirrors client apply in Materials/Edit.vue — keep behaviour in sync.
 */
final class MaterialCopilotPatch
{
    /**
     * @param  list<array{heading?: string, body?: string}>  $current
     * @param  list<array{heading?: string, body?: string}>  $incoming
     * @return list<array{heading: string, body: string}>
     */
    public static function mergeSections(array $current, array $incoming): array
    {
        $sections = array_map(
            static fn (array $sec): array => [
                'heading' => (string) ($sec['heading'] ?? ''),
                'body' => (string) ($sec['body'] ?? ''),
            ],
            array_values($current)
        );

        foreach (array_values($incoming) as $i => $sec) {
            $heading = (string) ($sec['heading'] ?? '');
            $body = (string) ($sec['body'] ?? '');
            $matched = -1;

            foreach ($sections as $j => $existing) {
                if (self::headingsMatch($existing['heading'], $heading)) {
                    $matched = $j;
                    break;
                }
            }

            if ($matched >= 0) {
                $sections[$matched]['heading'] = $heading;
                $sections[$matched]['body'] = $body;
            } elseif (isset($sections[$i]) && count($incoming) <= count($sections)) {
                $sections[$i]['heading'] = $heading;
                $sections[$i]['body'] = $body;
            } else {
                $sections[] = ['heading' => $heading, 'body' => $body];
            }
        }

        return $sections;
    }

    public static function headingsMatch(string $a, string $b): bool
    {
        $na = self::normalizeHeading($a);
        $nb = self::normalizeHeading($b);

        if ($na === '' || $nb === '') {
            return false;
        }

        return $na === $nb || str_contains($na, $nb) || str_contains($nb, $na);
    }

    private static function normalizeHeading(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/^\d+[\.\)\-:\s]+/u', '', $value) ?? $value;

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }
}
