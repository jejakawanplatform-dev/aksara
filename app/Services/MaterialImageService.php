<?php

namespace App\Services;

use App\Models\LearningMaterial;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MaterialImageService
{
    /** @var list<string> */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * List image files in the material's public storage folder (context-scoped).
     *
     * @return list<array{name: string, url: string, size: int, updated_at: string|null}>
     */
    public function list(LearningMaterial $material): array
    {
        $directory = $this->directory($material);
        $disk = Storage::disk('public');

        if (! $disk->exists($directory)) {
            return [];
        }

        $items = [];
        foreach ($disk->files($directory) as $path) {
            $name = basename($path);
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }
            if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            $items[] = [
                'name' => $name,
                'url' => '/storage/'.$path,
                'size' => (int) $disk->size($path),
                'updated_at' => $disk->lastModified($path)
                    ? date('c', $disk->lastModified($path))
                    : null,
            ];
        }

        usort($items, static fn (array $a, array $b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));

        return $items;
    }

    /**
     * Delete a basename under the material folder. Rejects path traversal.
     */
    public function delete(LearningMaterial $material, string $filename): void
    {
        $safe = $this->assertSafeBasename($filename);
        $path = $this->directory($material).'/'.$safe;
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            throw new InvalidArgumentException('File tidak ditemukan di konteks materi ini.');
        }

        if (! $disk->delete($path)) {
            throw new \RuntimeException('Gagal menghapus file gambar.');
        }
    }

    /**
     * Store an uploaded material image on the public disk and return a relative public path.
     */
    public function store(LearningMaterial $material, UploadedFile $file): string
    {
        $mime = strtolower((string) $file->getMimeType());
        $extension = $this->extensionFromMime($mime)
            ?? strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');

        return $this->storeBinary(
            $material,
            (string) file_get_contents($file->getRealPath()),
            $extension,
        );
    }

    /**
     * Store raw image bytes (from browser-compressed data URL) and return `/storage/...`.
     */
    public function storeBinary(LearningMaterial $material, string $binary, string $extension = 'jpg'): string
    {
        $extension = strtolower($extension);
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException('Ekstensi gambar tidak didukung: '.$extension);
        }

        if ($binary === '') {
            throw new InvalidArgumentException('Konten gambar kosong.');
        }

        // ~2.5MB binary safety (client targets ≤1.5MB after compress).
        if (strlen($binary) > 2.5 * 1024 * 1024) {
            throw new InvalidArgumentException('Gambar terlalu besar setelah kompresi.');
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $directory = $this->directory($material);
        $path = $directory.'/'.$filename;

        $ok = Storage::disk('public')->put($path, $binary);

        if (! $ok || ! Storage::disk('public')->exists($path)) {
            $absolute = Storage::disk('public')->path($path);
            $dir = dirname($absolute);
            $hint = is_dir($dir)
                ? (is_writable($dir) ? 'put() gagal tanpa alasan jelas' : 'direktori tidak writable oleh PHP-FPM')
                : 'direktori tujuan tidak ada';

            throw new \RuntimeException('Gagal menyimpan file gambar materi ('.$hint.').');
        }

        return '/storage/'.$path;
    }

    /**
     * Decode a browser data URL (`data:image/jpeg;base64,...`) into binary + extension.
     *
     * @return array{binary: string, extension: string, mime: string}
     */
    public function decodeDataUrl(string $dataUrl): array
    {
        if (! preg_match('#^data:(image/(jpeg|jpg|png|webp|gif));base64,(.+)$#is', trim($dataUrl), $m)) {
            throw new InvalidArgumentException('Format data URL gambar tidak valid.');
        }

        $mime = strtolower($m[1]);
        $binary = base64_decode($m[3], true);

        if ($binary === false || $binary === '') {
            throw new InvalidArgumentException('Gagal mendekode data gambar.');
        }

        return [
            'binary' => $binary,
            'extension' => $this->extensionFromMime($mime) ?? 'jpg',
            'mime' => $mime,
        ];
    }

    private function directory(LearningMaterial $material): string
    {
        return 'materials/'.$material->id;
    }

    private function assertSafeBasename(string $filename): string
    {
        $name = basename(str_replace(['\\', "\0"], '', trim($filename)));
        if ($name === '' || $name === '.' || $name === '..' || str_contains($name, '..')) {
            throw new InvalidArgumentException('Nama file tidak valid.');
        }

        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException('Ekstensi gambar tidak didukung: '.$extension);
        }

        return $name;
    }

    private function extensionFromMime(string $mime): ?string
    {
        return match (true) {
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => 'jpg',
            str_contains($mime, 'png') => 'png',
            str_contains($mime, 'webp') => 'webp',
            str_contains($mime, 'gif') => 'gif',
            default => null,
        };
    }
}
