<?php

namespace Tests\Unit;

use App\Support\MaterialContentHtml;
use Tests\TestCase;

class MaterialContentHtmlTest extends TestCase
{
    public function test_replaces_hallucinated_img_with_illustration_blockquote(): void
    {
        $html = '<p>Konten digital.</p><img src="https://example.com/fake.png" alt="Ilustrasi Konten Digital">';

        $result = MaterialContentHtml::sanitizeSectionBody($html);

        $this->assertStringNotContainsString('<img', $result);
        $this->assertStringContainsString('🖼️ Saran Ilustrasi:', $result);
        $this->assertStringContainsString('Ilustrasi Konten Digital', $result);
        $this->assertStringContainsString('<blockquote>', $result);
    }

    public function test_keeps_trusted_storage_and_data_uri_images(): void
    {
        $storage = '<p>A</p><img src="/storage/materials/1/abc.jpg" alt="Lokal">';
        $data = '<p>B</p><img src="data:image/png;base64,iVBORw0KGgo=" alt="Data">';

        $this->assertStringContainsString('<img src="/storage/materials/1/abc.jpg"', MaterialContentHtml::sanitizeSectionBody($storage));
        $this->assertStringContainsString('data:image/png;base64', MaterialContentHtml::sanitizeSectionBody($data));
    }

    public function test_sanitize_sections_normalizes_bodies(): void
    {
        $sections = MaterialContentHtml::sanitizeSections([
            [
                'heading' => '1. Topik',
                'body' => '<img src="https://cdn.invalid/x.jpg" alt="Bagan">',
            ],
        ]);

        $this->assertSame('1. Topik', $sections[0]['heading']);
        $this->assertStringContainsString('Bagan', $sections[0]['body']);
        $this->assertStringNotContainsString('<img', $sections[0]['body']);
    }

    public function test_normalizes_plain_text_illustration_tip_to_html_block(): void
    {
        $html = '<p>Konten digital adalah informasi daring.</p>'
            .'<p>Saran ilustrasi: ID: KontenDigital1, Prompt AI Image EN: Illustration of digital content, '
            .'Link Unsplash: https://unsplash.com/s/photos/digital-content, Sumber: Unsplash</p>';

        $result = MaterialContentHtml::sanitizeSectionBody($html);

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

        $result = MaterialContentHtml::sanitizeSectionBody($html);

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

    public function test_sanitize_is_idempotent_and_does_not_nest_illustration_blocks(): void
    {
        $html = '<p>Konten.</p>'
            .'<p>Saran ilustrasi: ID: Foo, Prompt AI Image EN: A diagram, '
            .'Link Unsplash: https://unsplash.com/s/photos/diagram, Sumber: Unsplash</p>';

        $once = MaterialContentHtml::sanitizeSectionBody($html);
        $twice = MaterialContentHtml::sanitizeSectionBody($once);
        $thrice = MaterialContentHtml::sanitizeSectionBody($twice);

        $this->assertSame($once, $twice);
        $this->assertSame($twice, $thrice);
        $this->assertSame(1, substr_count(strtolower($thrice), '<blockquote'));
        $this->assertSame(1, preg_match_all('/Cari\s*&(?:amp;)?\s*unduh\s+di\s+Unsplash/u', $thrice));
        $this->assertStringContainsString('<code>A diagram</code>', $thrice);
    }

    public function test_flattens_nested_illustration_blockquotes(): void
    {
        $nested = '<p>Merancang konten digital.</p>'
            .'<blockquote>'
            .'<p><strong>🖼️ Saran Ilustrasi:</strong> Ilustrasi merancang konten digital</p>'
            .'<blockquote>'
            .'<p><a href="https://unsplash.com/s/photos/x">Cari &amp; unduh di Unsplash</a> · '
            .'<a href="https://commons.wikimedia.org/w/index.php?search=x">Cari di Wikimedia Commons</a></p>'
            .'<p><em>Sumber: Unsplash / Wikimedia Commons (lisensi bebas). Unduh atau generate...</em></p>'
            .'<blockquote>'
            .'<p><a href="https://unsplash.com/s/photos/x">Cari &amp; unduh di Unsplash</a> · '
            .'<a href="https://commons.wikimedia.org/w/index.php?search=x">Cari di Wikimedia Commons</a></p>'
            .'<p><em>Sumber: Unsplash / Wikimedia Commons (lisensi bebas). Unduh atau generate...</em></p>'
            .'</blockquote>'
            .'</blockquote>'
            .'</blockquote>';

        $result = MaterialContentHtml::sanitizeSectionBody($nested);

        $this->assertSame(1, substr_count(strtolower($result), '<blockquote'));
        $this->assertSame(1, substr_count($result, '</blockquote>'));
        $this->assertSame(1, preg_match_all('/🖼️\s*Saran Ilustrasi:/u', $result));
        $this->assertSame(1, preg_match_all('/Cari\s*&(?:amp;)?\s*unduh\s+di\s+Unsplash/u', $result));
        $this->assertStringContainsString('Ilustrasi merancang konten digital', $result);
        $this->assertStringContainsString('🎯 Prompt AI Image:', $result);
        $this->assertStringContainsString('<code>', $result);
    }
}
