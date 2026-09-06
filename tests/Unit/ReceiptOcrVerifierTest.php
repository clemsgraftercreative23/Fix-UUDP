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

    private function mockClient(array $extractResult): GeminiOcrClient
    {
        $client = $this->createMock(GeminiOcrClient::class);
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
        config(['services.gemini.api_key' => null]);
        $verifier = new ReceiptOcrVerifier($this->createMock(GeminiOcrClient::class));

        $result = $verifier->read($this->receiptFile());

        $this->assertSame('unavailable', $result['status']);
    }

    public function test_read_is_unavailable_when_ocr_extraction_fails(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $client = $this->createMock(GeminiOcrClient::class);
        $client->method('extract')->willReturn([
            'ok' => false, 'no_invoice' => null, 'amount' => null, 'error' => 'timeout', 'raw' => [],
        ]);
        $verifier = new ReceiptOcrVerifier($client);

        $result = $verifier->read($this->receiptFile());

        $this->assertSame('unavailable', $result['status']);
    }

    public function test_read_skips_unsupported_file_types_without_calling_ocr(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $client = $this->createMock(GeminiOcrClient::class);
        $client->expects($this->never())->method('extract');
        $verifier = new ReceiptOcrVerifier($client);

        $textFile = new UploadedFile($this->textPath, 'note.txt', 'text/plain', null, true);
        $result = $verifier->read($textFile);

        $this->assertSame('skipped', $result['status']);
    }

    public function test_read_returns_the_extracted_invoice_number(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
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
        config(['services.gemini.api_key' => 'test-key']);
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
        config(['services.gemini.api_key' => 'test-key']);
        $verifier = new ReceiptOcrVerifier($this->mockClient([]));

        $result = $verifier->read($this->receiptFile());

        $this->assertSame('read', $result['status']);
        $this->assertNull($result['extracted_no_invoice']);
    }
}
