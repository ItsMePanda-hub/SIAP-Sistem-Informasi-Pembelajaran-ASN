<?php

namespace Tests\Unit;

use App\Support\NativeContentRenderer;
use PHPUnit\Framework\TestCase;

class NativeContentRendererTest extends TestCase
{
    public function test_heading_bab_detected(): void
    {
        $blocks = NativeContentRenderer::blocks("BAB 1 — KONSEP\nIsi paragraf.");
        $this->assertSame('heading', $blocks[0]['type']);
        $this->assertSame(2, $blocks[0]['level']);
        $this->assertSame('paragraph', $blocks[1]['type']);
    }

    public function test_section_headings_detected(): void
    {
        $blocks = NativeContentRenderer::blocks("Tujuan Pembelajaran\nIsi.\n\nGLOSARIUM\nIstilah.");
        $types = array_column($blocks, 'type');
        $this->assertSame(['heading', 'paragraph', 'heading', 'paragraph'], $types);
    }

    public function test_ordered_list_grouped(): void
    {
        $blocks = NativeContentRenderer::blocks("1. Pertama\n2. Kedua\n\nSesudah.");
        $this->assertSame('list', $blocks[0]['type']);
        $this->assertTrue($blocks[0]['ordered']);
        $this->assertSame(['Pertama', 'Kedua'], $blocks[0]['items']);
        $this->assertSame('paragraph', $blocks[1]['type']);
    }

    public function test_bullet_list_grouped(): void
    {
        $blocks = NativeContentRenderer::blocks("- Satu\n- Dua");
        $this->assertSame('list', $blocks[0]['type']);
        $this->assertFalse($blocks[0]['ordered']);
        $this->assertCount(2, $blocks[0]['items']);
    }

    public function test_blank_lines_skipped_and_inline_numbers_kept(): void
    {
        $blocks = NativeContentRenderer::blocks("Paragraf satu.\n\n\nParagraf dua dengan 1) inline.");
        $this->assertCount(2, $blocks);
        $this->assertSame('paragraph', $blocks[0]['type']);
        $this->assertSame('paragraph', $blocks[1]['type']);
    }

    public function test_paren_format_ordered_list(): void
    {
        $blocks = NativeContentRenderer::blocks("1) Pertama\n2) Kedua\n\nSesudah.");
        $this->assertSame('list', $blocks[0]['type']);
        $this->assertTrue($blocks[0]['ordered']);
        $this->assertSame(['Pertama', 'Kedua'], $blocks[0]['items']);
        $this->assertSame('paragraph', $blocks[1]['type']);
    }

    public function test_dot_format_still_works(): void
    {
        $blocks = NativeContentRenderer::blocks("1. Alpha\n2. Beta");
        $this->assertSame('list', $blocks[0]['type']);
        $this->assertTrue($blocks[0]['ordered']);
        $this->assertSame(['Alpha', 'Beta'], $blocks[0]['items']);
    }

    public function test_mixed_delimiter_grouped_in_one_list(): void
    {
        $blocks = NativeContentRenderer::blocks("1. Satu\n2) Dua\n3. Tiga");
        $this->assertCount(1, $blocks);
        $this->assertSame('list', $blocks[0]['type']);
        $this->assertTrue($blocks[0]['ordered']);
        $this->assertSame(['Satu', 'Dua', 'Tiga'], $blocks[0]['items']);
    }

    public function test_gap_breaks_list_into_two(): void
    {
        $blocks = NativeContentRenderer::blocks("1) A\n2) B\n\nParagraf.\n\n1) C\n2) D");
        $this->assertCount(3, $blocks);
        $this->assertSame('list', $blocks[0]['type']);
        $this->assertSame('paragraph', $blocks[1]['type']);
        $this->assertSame('list', $blocks[2]['type']);
        $this->assertSame(['C', 'D'], $blocks[2]['items']);
    }

    public function test_number_in_middle_not_list(): void
    {
        $blocks = NativeContentRenderer::blocks("Lihat 1) sebagai contoh di tengah kalimat.");
        $this->assertCount(1, $blocks);
        $this->assertSame('paragraph', $blocks[0]['type']);
        $blocks2 = NativeContentRenderer::blocks("Teks dengan 2. di tengah tidak jadi list.");
        $this->assertSame('paragraph', $blocks2[0]['type']);
    }

    public function test_xss_in_list_item_preserved_for_escaping(): void
    {
        $blocks = NativeContentRenderer::blocks("1) <script>alert(1)</script>\n2) <img onerror=alert(1)>");
        $this->assertSame('list', $blocks[0]['type']);
        $this->assertStringContainsString('<script>', $blocks[0]['items'][0]);
        $this->assertStringContainsString('<img', $blocks[0]['items'][1]);
    }

    public function test_xss_text_preserved_for_escaping(): void
    {
        $blocks = NativeContentRenderer::blocks('<script>alert(1)</script>');
        $this->assertSame('paragraph', $blocks[0]['type']);
        $this->assertStringContainsString('<script>', $blocks[0]['text']);
    }

    public function test_heading_and_bullet_still_compatible(): void
    {
        $blocks = NativeContentRenderer::blocks("BAB 1 — KONSEP\n- Bullet satu\n- Bullet dua\n\nGLOSARIUM\nIstilah.");
        $this->assertSame('heading', $blocks[0]['type']);
        $this->assertSame('list', $blocks[1]['type']);
        $this->assertFalse($blocks[1]['ordered']);
        $this->assertSame('heading', $blocks[2]['type']);
    }

    public function test_legacy_content_renders(): void
    {
        $legacy = "MODUL PEMBELAJARAN INTERNAL SIAP\n\nPendahuluan\nTeks biasa tanpa format khusus.";
        $blocks = NativeContentRenderer::blocks($legacy);
        $this->assertNotEmpty($blocks);
        $this->assertSame('paragraph', $blocks[0]['type']);
    }
}
