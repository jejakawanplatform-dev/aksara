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
        $directory = 'materials/'.$material->id;
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
