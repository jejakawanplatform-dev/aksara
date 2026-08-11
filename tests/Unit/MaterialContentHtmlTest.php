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

namespace Tests\Unit;

use App\Support\MaterialContentHtml;
use Tests\TestCase;

class MaterialContentHtmlTest extends TestCase
{
    public function test_sanitize_removes_hallucinated_img_without_teacher_tip_block(): void
    {
        $html = '<p>Konten digital.</p><img src="https://example.com/fake.png" alt="Ilustrasi Konten Digital">';

        $result = MaterialContentHtml::sanitizeSectionBody($html);

        $this->assertStringNotContainsString('<img', $result);
        $this->assertStringNotContainsString('Saran Ilustrasi', $result);
        $this->assertStringContainsString('Konten digital.', $result);
    }

    public function test_keeps_trusted_storage_and_data_uri_images(): void
    {
        $storage = '<p>A</p><img src="/storage/materials/1/abc.jpg" alt="Lokal">';
        $data = '<p>B</p><img src="data:image/png;base64,iVBORw0KGgo=" alt="Data">';

        $this->assertStringContainsString('<img src="/storage/materials/1/abc.jpg"', MaterialContentHtml::sanitizeSectionBody($storage));
        $this->assertStringContainsString('data:image/png;base64', MaterialContentHtml::sanitizeSectionBody($data));
    }

    public function test_sanitize_sections_strips_teacher_illustration_blocks(): void
    {
        $sections = MaterialContentHtml::sanitizeSections([
            [
                'heading' => '1. Topik',
                'body' => '<p>Isi.</p>'.MaterialContentHtml::illustrationBlockHtml('Bagan', 'A diagram'),
            ],
        ]);

        $this->assertSame('1. Topik', $sections[0]['heading']);
        $this->assertStringContainsString('Isi.', $sections[0]['body']);
        $this->assertStringNotContainsString('Saran Ilustrasi', $sections[0]['body']);
        $this->assertStringNotContainsString('<blockquote>', $sections[0]['body']);
    }

    public function test_normalize_plain_text_illustration_tip_to_html_block(): void
    {
        $html = '<p>Konten digital adalah informasi daring.</p>'
            .'<p>Saran ilustrasi: ID: KontenDigital1, Prompt AI Image EN: Illustration of digital content, '
            .'Link Unsplash: https://unsplash.com/s/photos/digital-content, Sumber: Unsplash</p>';

        $result = MaterialContentHtml::normalizeIllustrationSuggestions($html);

        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('🖼️ Saran Ilustrasi:', $result);
        $this->assertStringContainsString('KontenDigital1', $result);
        $this->assertStringContainsString('🎯 Prompt AI Image:', $result);
        $this->assertStringContainsString('<code>Illustration of digital content</code>', $result);
        $this->assertStringContainsString('href="https://unsplash.com/s/photos/digital-content"', $result);
        $this->assertStringNotContainsString('Saran ilustrasi: ID:', $result);
    }

    public function test_always_includes_copyable_prompt_even_when_ai_omits_it(): void
    {
        $html = '<blockquote><p><strong>🖼️ Saran Ilustrasi:</strong> Bagan siklus air</p>'
            .'<p><a href="https://unsplash.com/s/photos/water">Cari &amp; unduh di Unsplash</a></p>'
            .'<p><em>Sumber: Unsplash</em></p></blockquote>';

        $result = MaterialContentHtml::ensureIllustrationPrompts($html);

        $this->assertStringContainsString('🎯 Prompt AI Image:', $result);
        $this->assertStringContainsString('<code>', $result);
        $this->assertStringContainsString('Bagan siklus air', $result);
        $this->assertSame(1, substr_count(strtolower($result), '<blockquote'));
    }

    public function test_for_student_strips_illustration_teacher_hints(): void
    {
        $html = '<p>Materi untuk siswa.</p>'
            .MaterialContentHtml::illustrationBlockHtml(
                'Ilustrasi atom',
                'Simple atom diagram for textbook',
                'https://unsplash.com/s/photos/atom',
                null,
                'Unsplash'
            )
            .'<p>Paragraf lanjut.</p>';

        $student = MaterialContentHtml::forStudent($html);

        $this->assertStringContainsString('Materi untuk siswa.', $student);
        $this->assertStringContainsString('Paragraf lanjut.', $student);
        $this->assertStringNotContainsString('Saran Ilustrasi', $student);
        $this->assertStringNotContainsString('Prompt AI Image', $student);
        $this->assertStringNotContainsString('Unsplash', $student);
        $this->assertStringNotContainsString('<blockquote>', $student);
    }

    public function test_extract_tips_from_sections_and_clean_bodies(): void
    {
        $extracted = MaterialContentHtml::extractIllustrationTipsFromSections([
            [
                'heading' => '1. Pengenalan',
                'body' => '<p>Materi.</p>'.MaterialContentHtml::illustrationBlockHtml(
                    'Ilustrasi konten digital',
                    'Educational textbook illustration: digital content',
                    'https://unsplash.com/s/photos/digital',
                    'https://commons.wikimedia.org/w/index.php?search=digital'
                ),
            ],
        ]);

        $this->assertCount(1, $extracted['tips']);
        $this->assertSame('1. Pengenalan', $extracted['tips'][0]['sectionHeading']);
        $this->assertStringContainsString('konten digital', strtolower($extracted['tips'][0]['description']));
        $this->assertStringContainsString('Educational textbook', $extracted['tips'][0]['prompt']);
        $this->assertStringContainsString('Materi.', $extracted['sections'][0]['body']);
        $this->assertStringNotContainsString('Saran Ilustrasi', $extracted['sections'][0]['body']);
    }

    public function test_sanitize_is_idempotent_after_stripping_tips(): void
    {
        $html = '<p>Konten.</p>'
            .'<p>Saran ilustrasi: ID: Foo, Prompt AI Image EN: A diagram, '
            .'Link Unsplash: https://unsplash.com/s/photos/diagram, Sumber: Unsplash</p>';

        $once = MaterialContentHtml::sanitizeSectionBody($html);
        $twice = MaterialContentHtml::sanitizeSectionBody($once);

        $this->assertSame($once, $twice);
        $this->assertStringContainsString('Konten.', $once);
        $this->assertStringNotContainsString('Saran', $once);
        $this->assertStringNotContainsString('<blockquote>', $once);
    }

    public function test_sanitize_strips_nested_illustration_blockquotes(): void
    {
        $nested = '<p>Merancang konten digital.</p>'
            .'<blockquote>'
            .'<p><strong>🖼️ Saran Ilustrasi:</strong> Ilustrasi merancang konten digital</p>'
            .'<blockquote>'
            .'<p><a href="https://unsplash.com/s/photos/x">Cari &amp; unduh di Unsplash</a></p>'
            .'</blockquote>'
            .'</blockquote>';

        $result = MaterialContentHtml::sanitizeSectionBody($nested);

        $this->assertStringContainsString('Merancang konten digital.', $result);
        $this->assertStringNotContainsString('Saran Ilustrasi', $result);
        $this->assertStringNotContainsString('<blockquote>', $result);
    }
}
