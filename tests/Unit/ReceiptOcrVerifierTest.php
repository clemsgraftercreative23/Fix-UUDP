<?php

namespace Tests\Unit;

use App\Support\GeminiOcrClient;
use App\Support\ReceiptOcrVerifier;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReceiptOcrVerifierTest extends TestCase
{
    private string $imagePath;
    private string $textPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->imagePath = sys_get_temp_dir() . '/receipt_ocr_test_' . uniqid() . '.jpg';
        $image = imagecreatetruecolor(2, 2);
        imagejpeg($image, $this->imagePath);
        imagedestroy($image);

        $this->textPath = sys_get_temp_dir() . '/receipt_ocr_test_' . uniqid() . '.txt';
        file_put_contents($this->textPath, 'not an image');
    }

    protected function tearDown(): void
    {
        foreach ([$this->imagePath, $this->textPath] as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    private function receiptFile(): UploadedFile
    {
        return new UploadedFile($this->imagePath, 'receipt.jpg', 'image/jpeg', null, true);
    }

    /**
     * isConfigured() defaults to false on an auto-mock (PHPUnit's default
     * return for an unstubbed bool method), so every test that wants
     * read() to actually reach extract() must stub it true -- this is what
     * used to be a single `config(['services.gemini.api_key' => ...])` call
     * before that check moved from ReceiptOcrVerifier into each client (so
     * the check is always asking "is the ACTIVE client ready", not
     * hardcoded to Gemini's config key regardless of provider).
     */
    private function mockClient(array $extractResult): GeminiOcrClient
    {
        $client = $this->createMock(GeminiOcrClient::class);
        $client->method('isConfigured')->willReturn(true);
        $client->method('extract')->willReturn(array_merge([
            'ok' => true,
            'no_invoice' => null,
            'amount' => null,
            'error' => null,
            'raw' => [],
        ], $extractResult));

        return $client;
    }

    public function test_read_is_unavailable_when_api_key_not_configured(): void
    {
        $client = $this->createMock(GeminiOcrClient::class);
        $client->method('isConfigured')->willReturn(false);
        $verifier = new ReceiptOcrVerifier($client);

        $result = $verifier->read($this->receiptFile());

        $this->assertSame('unavailable', $result['status']);
    }

    public function test_read_is_unavailable_when_ocr_extraction_fails(): void
    {
        $client = $this->createMock(GeminiOcrClient::class);
        $client->method('isConfigured')->willReturn(true);
        $client->method('extract')->willReturn([
            'ok' => false, 'no_invoice' => null, 'amount' => null, 'error' => 'timeout', 'raw' => [],
        ]);
        $verifier = new ReceiptOcrVerifier($client);

        $result = $verifier->read($this->receiptFile());

        $this->assertSame('unavailable', $result['status']);
    }

    public function test_read_skips_unsupported_file_types_without_calling_ocr(): void
    {
        $client = $this->createMock(GeminiOcrClient::class);
        $client->method('isConfigured')->willReturn(true);
        $client->expects($this->never())->method('extract');
        $verifier = new ReceiptOcrVerifier($client);

        $textFile = new UploadedFile($this->textPath, 'note.txt', 'text/plain', null, true);
        $result = $verifier->read($textFile);

        $this->assertSame('skipped', $result['status']);
    }

    public function test_read_returns_the_extracted_invoice_number(): void
    {
        $verifier = new ReceiptOcrVerifier($this->mockClient([
            'no_invoice' => 'INV-001',
            'amount' => 150000.0,
        ]));

        $result = $verifier->read($this->receiptFile());

        $this->assertSame('read', $result['status']);
        $this->assertSame('INV-001', $result['extracted_no_invoice']);
    }

    public function test_read_does_not_flag_anything_when_amount_differs_from_typed_value(): void
    {
        // Amount is manual input only -- OCR reading a different amount off
        // the receipt must never affect the read result or block anything.
        $verifier = new ReceiptOcrVerifier($this->mockClient([
            'no_invoice' => 'INV-001',
            'amount' => 999999.0,
        ]));

        $result = $verifier->read($this->receiptFile());

        $this->assertSame('read', $result['status']);
        $this->assertSame('INV-001', $result['extracted_no_invoice']);
        $this->assertSame(999999.0, $result['extracted_amount']);
    }

    public function test_read_still_succeeds_when_no_invoice_number_is_found_on_receipt(): void
    {
        // A real receipt missing only the invoice number field -- merchant
        // and amount ARE present, distinguishing this from
        // test_read_flags_a_photo_that_is_not_a_receipt_at_all() below, where
        // nothing at all comes back.
        $verifier = new ReceiptOcrVerifier($this->mockClient([
            'merchant_name' => 'Toko Contoh',
            'amount' => 50000.0,
        ]));

        $result = $verifier->read($this->receiptFile());

        $this->assertSame('read', $result['status']);
        $this->assertNull($result['extracted_no_invoice']);
    }

    public function test_read_flags_a_photo_that_is_not_a_receipt_at_all(): void
    {
        // Nothing extracted whatsoever -- no invoice, no amount, no merchant,
        // no date. The strongest available signal (works the same regardless
        // of OCR provider) that the uploaded photo isn't a receipt/invoice at
        // all, not just a receipt missing one field. Still fails open --
        // this is a warning to re-upload, not a hard block.
        $verifier = new ReceiptOcrVerifier($this->mockClient([]));

        $result = $verifier->read($this->receiptFile());

        $this->assertSame('not_receipt', $result['status']);
        $this->assertNull($result['extracted_no_invoice']);
        $this->assertStringContainsString('bukan invoice', $result['message']);
    }

    /**
     * Real production case (Sep 22 2026): a non-receipt photo (garbled
     * ocr_text) came back from Veryfi with invoice_number/total/date all
     * null, but a populated (hallucinated, matched from a vendor/logo
     * database against nonsense text) merchant_name -- which the OLD
     * heuristic required to ALSO be null, so this case slipped through as a
     * "successful read" instead of being flagged. merchant_name must not be
     * part of the not_receipt check for this exact reason.
     */
    public function test_read_flags_a_non_receipt_even_when_merchant_name_is_hallucinated(): void
    {
        $verifier = new ReceiptOcrVerifier($this->mockClient([
            'merchant_name' => 'AKARSA HEKSA BERSAUDARA ENTERPRISE DIGITAL PLATFORM',
        ]));

        $result = $verifier->read($this->receiptFile());

        $this->assertSame('not_receipt', $result['status']);
    }

    public function test_default_client_resolves_to_veryfi_by_default(): void
    {
        config(['services.ocr.provider' => null, 'services.veryfi.client_id' => null]);
        $verifier = new ReceiptOcrVerifier();

        // No credentials configured for either provider -- Veryfi is picked
        // as the default, and with nothing configured it reports unavailable
        // rather than erroring.
        $result = $verifier->read($this->receiptFile());

        $this->assertSame('unavailable', $result['status']);
    }
}
