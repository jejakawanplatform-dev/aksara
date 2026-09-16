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

namespace App\Services;

use App\Models\LearningMaterial;
use App\Models\LearningPlan;
use App\Support\MaterialContentHtml;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class MaterialExportService
{
    /**
     * Mengekstrak judul, seksi konten bersih, dan refleksi dari model LearningMaterial.
     *
     * @return array{title: string, sections: list<array{heading: string, body: string, rawBody: string}>, reflections: list<string>, plan: array{topic: string, subject: string, code: string, className: string, grade: string, duration: int, teacher: string, phase: string, status: string}}
     */
    public function extractMaterialData(LearningMaterial $material): array
    {
        $material->loadMissing(['plan.subject', 'plan.class', 'plan.teacher']);
        $plan = $material->plan;

        $content = is_array($material->content) ? $material->content : [];
        $materialData = (isset($content['material']) && is_array($content['material'])) ? $content['material'] : [];

        // Resolusi Judul
        $title = $plan ? $plan->topic : 'Materi Pembelajaran';
        if (isset($content['title']) && is_string($content['title']) && trim($content['title']) !== '') {
            $title = trim($content['title']);
        } elseif (isset($materialData['title']) && is_string($materialData['title']) && trim($materialData['title']) !== '') {
            $title = trim($materialData['title']);
        }

        // Resolusi Seksi Konten
        $rawSections = (isset($content['sections']) && is_iterable($content['sections']))
            ? $content['sections']
            : ((isset($materialData['sections']) && is_iterable($materialData['sections'])) ? $materialData['sections'] : []);

        $sections = [];
        foreach ($rawSections as $sec) {
            $heading = '';
            $bodyHtml = '';
            if (is_array($sec)) {
                $heading = isset($sec['heading']) && is_scalar($sec['heading']) ? trim((string) $sec['heading']) : '';
                $bodyHtml = isset($sec['body']) && is_string($sec['body']) ? $sec['body'] : '';
            } elseif (is_scalar($sec)) {
                $heading = trim((string) $sec);
            }

            $sanitizedBody = MaterialContentHtml::forStudent($bodyHtml);
            $sections[] = [
                'heading' => $heading,
                'body' => $sanitizedBody,
                'rawBody' => $this->htmlToPlainText($sanitizedBody),
            ];
        }

        // Resolusi Pertanyaan Refleksi
        $rawReflection = $content['reflectionQuestion'] ?? ($materialData['reflectionQuestion'] ?? null);
        $reflectionList = is_array($rawReflection)
            ? $rawReflection
            : (is_string($rawReflection) && trim($rawReflection) !== '' ? [$rawReflection] : []);

        $reflections = [];
        foreach ($reflectionList as $item) {
            if (is_array($item)) {
                $scalars = [];
                foreach ($item as $subItem) {
                    if (is_scalar($subItem)) {
                        $scalars[] = trim((string) $subItem);
                    }
                }
                $str = implode('; ', array_filter($scalars));
                if ($str !== '') {
                    $reflections[] = $str;
                }
            } elseif (is_scalar($item) && trim((string) $item) !== '') {
                $reflections[] = trim((string) $item);
            }
        }

        return [
            'title' => $title,
            'sections' => $sections,
            'reflections' => $reflections,
            'plan' => [
                'topic' => $plan ? $plan->topic : 'Materi',
                'subject' => $plan && $plan->subject ? $plan->subject->name : '—',
                'code' => $plan && $plan->subject ? ($plan->subject->code ?: '—') : '—',
                'className' => $plan && $plan->class ? $plan->class->name : ($plan && $plan->grade ? 'Kelas '.$plan->grade : '—'),
                'grade' => $plan && $plan->grade ? (string) $plan->grade : '—',
                'duration' => $plan ? $plan->duration_minutes : 0,
                'teacher' => $plan && $plan->teacher ? $plan->teacher->name : '—',
                'phase' => $plan ? $plan->phase : 'D',
                'status' => $material->status->label(),
            ],
        ];
    }

    /**
     * Mengekspor materi pembelajaran ke berkas dokumen Word (.docx).
     */
    public function exportWord(LearningMaterial $material): string
    {
        $data = $this->extractMaterialData($material);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        // Judul Utama Dokumen
        $section->addText(
            'BAHAN AJAR / MATERI PEMBELAJARAN',
            ['bold' => true, 'size' => 10, 'color' => '0D9488', 'allCaps' => true],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60]
        );
        $section->addText(
            mb_strtoupper($data['title']),
            ['bold' => true, 'size' => 14, 'color' => '0F766E'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 180]
        );

        // Tabel Metadata Pembelajaran
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 90]);

        $table->addRow();
        $table->addCell(2500, ['bgColor' => 'F1F5F9'])->addText('Mata Pelajaran', ['bold' => true, 'size' => 9]);
        $table->addCell(6500)->addText($data['plan']['subject'].' ('.$data['plan']['code'].')', ['size' => 9]);

        $table->addRow();
        $table->addCell(2500, ['bgColor' => 'F1F5F9'])->addText('Kelas / Fase / Durasi', ['bold' => true, 'size' => 9]);
        $table->addCell(6500)->addText($data['plan']['className'].' | Fase '.$data['plan']['phase'].' | '.$data['plan']['duration'].' Menit', ['size' => 9]);

        $table->addRow();
        $table->addCell(2500, ['bgColor' => 'F1F5F9'])->addText('Guru Pengampu', ['bold' => true, 'size' => 9]);
        $table->addCell(6500)->addText($data['plan']['teacher'].' ('.$data['plan']['status'].')', ['size' => 9]);

        $section->addTextBreak(1);

        // Seksi-seksi Konten Materi
        foreach ($data['sections'] as $secIndex => $sec) {
            $headingText = $sec['heading'] !== '' ? $sec['heading'] : 'Bagian '.($secIndex + 1);
            $section->addText(
                $headingText,
                ['bold' => true, 'size' => 12, 'color' => '0F766E'],
                ['spaceBefore' => 180, 'spaceAfter' => 80]
            );

            $this->renderHtmlToWord($section, $sec['body']);
        }

        // Pertanyaan Refleksi (jika ada)
        if (! empty($data['reflections'])) {
            $section->addTextBreak(1);
            $section->addText(
                'Pertanyaan Refleksi Siswa',
                ['bold' => true, 'size' => 11, 'color' => '0F766E'],
                ['spaceBefore' => 120, 'spaceAfter' => 60]
            );

            $calloutTable = $section->addTable([
                'borderSize' => 8,
                'borderColor' => '0D9488',
                'bgColor' => 'F0FDFA',
                'cellMargin' => 120,
            ]);
            $calloutTable->addRow();
            $cell = $calloutTable->addCell(9000);

            foreach ($data['reflections'] as $refIndex => $refItem) {
                $cell->addText(($refIndex + 1).'. '.$refItem, ['size' => 10, 'italic' => true]);
            }
        }

        // Footer dengan nomor halaman
        $footer = $section->addFooter();
        $footer->addPreserveText(
            'Aksara — Platform Pembelajaran Berbantuan AI · Halaman {PAGE} dari {NUMPAGES}',
            ['size' => 8, 'color' => '64748B'],
            ['alignment' => Jc::CENTER]
        );

        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        if ($tempFile === false) {
            throw new \RuntimeException('Gagal membuat file sementara untuk Word.');
        }

        $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        $content = file_get_contents($tempFile) ?: '';
        @unlink($tempFile);

        return $content;
    }

    /**
     * Mengekspor materi pembelajaran ke berkas teks Markdown (.md).
     */
    public function exportMarkdown(LearningMaterial $material): string
    {
        $data = $this->extractMaterialData($material);

        $lines = [];
        $lines[] = '---';
        $lines[] = 'title: "'.$this->escapeYaml($data['title']).'"';
        $lines[] = 'subject: "'.$this->escapeYaml($data['plan']['subject']).'"';
        $lines[] = 'class: "'.$this->escapeYaml($data['plan']['className']).'"';
        $lines[] = 'grade: "'.$this->escapeYaml($data['plan']['grade']).'"';
        $lines[] = 'teacher: "'.$this->escapeYaml($data['plan']['teacher']).'"';
        $lines[] = 'status: "'.$this->escapeYaml($data['plan']['status']).'"';
        $lines[] = 'exported_at: "'.now()->toIso8601String().'"';
        $lines[] = '---';
        $lines[] = '';
        $lines[] = '# '.$data['title'];
        $lines[] = '';
        $lines[] = '**Mata Pelajaran:** '.$data['plan']['subject'].' ('.$data['plan']['code'].')  ';
        $lines[] = '**Kelas / Fase:** '.$data['plan']['className'].' (Fase '.$data['plan']['phase'].')  ';
        $lines[] = '**Guru Pengampu:** '.$data['plan']['teacher'].'  ';
        $lines[] = '**Alokasi Waktu:** '.$data['plan']['duration'].' Menit  ';
        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';

        foreach ($data['sections'] as $secIndex => $sec) {
            $heading = $sec['heading'] !== '' ? $sec['heading'] : 'Bagian '.($secIndex + 1);
            $lines[] = '## '.$heading;
            $lines[] = '';
            $markdownBody = $this->htmlToMarkdown($sec['body']);
            $lines[] = $markdownBody;
            $lines[] = '';
        }

        if (! empty($data['reflections'])) {
            $lines[] = '## Pertanyaan Refleksi';
            $lines[] = '';
            foreach ($data['reflections'] as $refIndex => $refItem) {
                $lines[] = '> **'.($refIndex + 1).'.** '.$refItem;
                $lines[] = '>';
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Menyiapkan data terstruktur untuk pratinjau cetak PDF Blade.
     *
     * @return array{title: string, sections: list<array{heading: string, body: string}>, reflections: list<string>, plan: array{topic: string, subject: string, code: string, className: string, grade: string, duration: int, teacher: string, phase: string, status: string}}
     */
    public function preparePrintData(LearningMaterial $material): array
    {
        $extracted = $this->extractMaterialData($material);

        return [
            'title' => $extracted['title'],
            'sections' => array_map(fn ($s) => [
                'heading' => $s['heading'],
                'body' => $s['body'],
            ], $extracted['sections']),
            'reflections' => $extracted['reflections'],
            'plan' => $extracted['plan'],
        ];
    }

    /**
     * Mengonversi potongan HTML menjadi elemen paragraf Word.
     *
     * @param  \PhpOffice\PhpWord\Element\Section  $section
     */
    private function renderHtmlToWord($section, string $html): void
    {
        if (trim($html) === '') {
            return;
        }

        // Pisahkan blok berdasarkan tag paragraf atau list item
        $clean = preg_replace('/<\/(p|div|h[1-6])>/i', "\n", $html) ?: $html;
        $clean = preg_replace('/<br\s*\/?>/i', "\n", $clean) ?: $clean;

        // Tangani list item
        $clean = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "• $1\n", $clean) ?: $clean;

        // Bersihkan seluruh tag sisa
        $paragraphs = explode("\n", $clean);
        foreach ($paragraphs as $p) {
            $text = trim(html_entity_decode(strip_tags($p), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($text === '') {
                continue;
            }

            if (str_starts_with($text, '• ')) {
                $section->addListItem(mb_substr($text, 2), 0, ['size' => 10], ['spaceAfter' => 40]);
            } else {
                $section->addText($text, ['size' => 10], ['spaceAfter' => 60]);
            }
        }
    }

    /**
     * Konversi HTML ke teks murni.
     */
    private function htmlToPlainText(string $html): string
    {
        $text = preg_replace('/<\/(p|div|h[1-6]|li)>/i', "\n", $html) ?: $html;
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text) ?: $text;

        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Konversi HTML kaya TipTap ke teks format Markdown sederhana.
     */
    private function htmlToMarkdown(string $html): string
    {
        $md = $html;

        // Bold & Italic
        $md = preg_replace('/<(strong|b)>(.*?)<\/(strong|b)>/is', '**$2**', $md) ?: $md;
        $md = preg_replace('/<(em|i)>(.*?)<\/(em|i)>/is', '*$2*', $md) ?: $md;

        // Code inline
        $md = preg_replace('/<code>(.*?)<\/code>/is', '`$1`', $md) ?: $md;

        // List item
        $md = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "- $1\n", $md) ?: $md;

        // Blockquote
        $md = preg_replace('/<blockquote[^>]*>(.*?)<\/blockquote>/is', "> $1\n\n", $md) ?: $md;

        // Paragraf & Break
        $md = preg_replace('/<\/(p|div)>/i', "\n\n", $md) ?: $md;
        $md = preg_replace('/<br\s*\/?>/i', "\n", $md) ?: $md;

        // Strip tag sisa dan decode entitas HTML
        $cleaned = html_entity_decode(strip_tags($md), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalisasi multiple empty lines
        return trim((string) preg_replace("/\n{3,}/", "\n\n", $cleaned));
    }

    /**
     * Escape string untuk YAML frontmatter.
     */
    private function escapeYaml(string $value): string
    {
        return str_replace(['"', "\n", "\r"], ['\"', ' ', ' '], trim($value));
    }
}
