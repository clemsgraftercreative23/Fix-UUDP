<?php

namespace Tests\Unit;

use App\Support\EvidenceFileNaming;
use Tests\TestCase;

class EvidenceFileNamingTest extends TestCase
{
    public function test_real_jpeg_mime_type_wins_even_with_misleading_client_extension(): void
    {
        // Regression: a real .jfif upload (valid JPEG bytes) used to be saved
        // as literally ".jfif", which many servers don't serve as inline-viewable.
        $this->assertSame('jpg', EvidenceFileNaming::resolveExtension('image/jpeg', 'jfif'));
    }

    public function test_recognized_mime_types_map_to_their_canonical_extension(): void
    {
        $this->assertSame('png', EvidenceFileNaming::resolveExtension('image/png', 'png'));
        $this->assertSame('gif', EvidenceFileNaming::resolveExtension('image/gif', 'gif'));
        $this->assertSame('webp', EvidenceFileNaming::resolveExtension('image/webp', 'webp'));
        $this->assertSame('pdf', EvidenceFileNaming::resolveExtension('application/pdf', 'pdf'));
    }

    public function test_unrecognized_mime_falls_back_to_whitelisted_client_extension(): void
    {
        $this->assertSame('png', EvidenceFileNaming::resolveExtension('application/octet-stream', 'png'));
    }

    public function test_jpeg_client_extension_is_normalized_to_jpg(): void
    {
        $this->assertSame('jpg', EvidenceFileNaming::resolveExtension('application/octet-stream', 'jpeg'));
    }

    public function test_unrecognized_mime_and_unsafe_client_extension_defaults_to_jpg(): void
    {
        // e.g. HEIC photos, SVG, ICO, or no extension at all.
        $this->assertSame('jpg', EvidenceFileNaming::resolveExtension('image/heic', 'heic'));
        $this->assertSame('jpg', EvidenceFileNaming::resolveExtension('image/svg+xml', 'svg'));
        $this->assertSame('jpg', EvidenceFileNaming::resolveExtension('', ''));
    }

    public function test_generated_filename_uses_the_resolved_extension(): void
    {
        $filename = EvidenceFileNaming::generateFilename('image/jpeg', 'jfif');

        $this->assertStringEndsWith('.jpg', $filename);
        $this->assertStringStartsWith('bukti_', $filename);
    }
}
