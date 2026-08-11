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
 * One-shot idempotent inserter for source file headers.
 * Usage: php scripts/insert-file-headers.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);

$phpHeader = <<<'HDR'
/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 */

HDR;

$vueHeader = <<<'HDR'
<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->

HDR;

$jsHeader = <<<'HDR'
/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 */

HDR;

$bladeHeader = <<<'HDR'
{{--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
--}}

HDR;

$globs = [
    'app/**/*.php',
    'bootstrap/*.php',
    'config/*.php',
    'database/migrations/*.php',
    'database/seeders/*.php',
    'database/factories/*.php',
    'routes/**/*.php',
    'resources/js/**/*.vue',
    'resources/js/**/*.js',
    'resources/css/**/*.css',
    'resources/views/**/*.blade.php',
    'tests/**/*.php',
];

function hasMarker(string $content): bool
{
    return str_contains($content, '@copyright')
        || str_contains($content, 'SPDX-License-Identifier')
        || str_contains($content, 'Aksara —');
}

/**
 * @return list<string>
 */
function expandGlob(string $root, string $pattern): array
{
    $full = $root.'/'.$pattern;
    $matches = glob($full, GLOB_BRACE) ?: [];

    // Recursive ** support via RecursiveDirectoryIterator when glob lacks **
    if (str_contains($pattern, '**/')) {
        $matches = [];
        [$base, $rest] = explode('**/', $pattern, 2);
        $dir = rtrim($root.'/'.$base, '/');
        if (! is_dir($dir)) {
            return [];
        }
        $ext = pathinfo($rest, PATHINFO_EXTENSION);
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            /** @var SplFileInfo $file */
            if (! $file->isFile()) {
                continue;
            }
            if ($ext !== '' && strtolower($file->getExtension()) !== strtolower($ext)) {
                continue;
            }
            if (str_ends_with($rest, '.blade.php') && ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $matches[] = $file->getPathname();
        }
    }

    return array_values(array_unique($matches));
}

$files = [];
foreach ($globs as $pattern) {
    foreach (expandGlob($root, $pattern) as $path) {
        // Skip bootstrap cache
        if (str_contains($path, '/bootstrap/cache/')) {
            continue;
        }
        $files[$path] = true;
    }
}

$updated = 0;
$skipped = 0;

foreach (array_keys($files) as $path) {
    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    if (hasMarker($content)) {
        $skipped++;

        continue;
    }

    $rel = str_replace($root.'/', '', $path);
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $isBlade = str_ends_with($path, '.blade.php');
    $new = null;

    if ($isBlade) {
        $new = $bladeHeader.$content;
    } elseif ($ext === 'vue') {
        $new = $vueHeader.$content;
    } elseif ($ext === 'js' || $ext === 'css') {
        if (str_starts_with(ltrim($content), '#!')) {
            $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
            $shebang = array_shift($lines)."\n";
            $rest = implode("\n", $lines);
            if (! str_ends_with($rest, "\n") && $rest !== '') {
                $rest .= "\n";
            }
            $new = $shebang.$jsHeader.$rest;
        } else {
            $new = $jsHeader.$content;
        }
    } elseif ($ext === 'php') {
        if (preg_match('/^\s*<\?php\s*(?:declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*)?/s', $content, $m)) {
            $insertAt = strlen($m[0]);
            $before = substr($content, 0, $insertAt);
            $after = substr($content, $insertAt);
            // Ensure newline after php open / declare
            if (! str_ends_with($before, "\n")) {
                $before .= "\n";
            }
            $new = $before."\n".$phpHeader.ltrim($after, "\n");
            // Prefer single blank line after header before next code if needed
            $new = preg_replace("/\n{3,}/", "\n\n", $new) ?? $new;
        } else {
            $new = "<?php\n\n".$phpHeader.$content;
        }
    }

    if ($new === null || $new === $content) {
        $skipped++;

        continue;
    }

    file_put_contents($path, $new);
    $updated++;
    echo "updated: {$rel}\n";
}

echo "\nDone. updated={$updated} skipped={$skipped} total=".count($files)."\n";
