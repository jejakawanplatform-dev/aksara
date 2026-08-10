<?php

namespace Tests\Unit;

use App\Support\MaterialCopilotPatch;
use PHPUnit\Framework\TestCase;

class MaterialCopilotPatchTest extends TestCase
{
    public function test_patch_mengganti_seksi_cocok_tanpa_menghapus_seksi_lain(): void
    {
        $current = [
            ['heading' => '1. Konsep', 'body' => '<p>Lama A</p>'],
            ['heading' => '2. Penerapan', 'body' => '<p>Lama B tetap</p>'],
        ];

        $incoming = [
            ['heading' => '1. Konsep', 'body' => '<p>Baru A dari Co-Pilot</p>'],
        ];

        $merged = MaterialCopilotPatch::mergeSections($current, $incoming);

        $this->assertCount(2, $merged);
        $this->assertSame('<p>Baru A dari Co-Pilot</p>', $merged[0]['body']);
        $this->assertSame('<p>Lama B tetap</p>', $merged[1]['body']);
    }

    public function test_heading_match_abaikan_nomor_awalan(): void
    {
        $this->assertTrue(MaterialCopilotPatch::headingsMatch('1. Konsep', 'Konsep'));
        $this->assertFalse(MaterialCopilotPatch::headingsMatch('Konsep', 'Penerapan'));
    }
}
