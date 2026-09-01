<?php

namespace Tests\Unit;

use App\Support\DuplicateInvoiceChecker;
use Tests\TestCase;

class DuplicateInvoiceCheckerTest extends TestCase
{
    public function test_normalize_number_trims_whitespace(): void
    {
        $this->assertSame('INV-001', DuplicateInvoiceChecker::normalizeNumber('  INV-001  '));
    }

    public function test_normalize_number_casts_null_to_empty_string(): void
    {
        $this->assertSame('', DuplicateInvoiceChecker::normalizeNumber(null));
    }

    public function test_normalize_number_reduces_whitespace_only_input_to_empty_string(): void
    {
        $this->assertSame('', DuplicateInvoiceChecker::normalizeNumber('   '));
    }

    public function test_normalize_number_is_idempotent_so_check_and_store_stay_consistent(): void
    {
        // The check endpoint and the reimbursement "store" methods must both
        // run raw input through this same normalization, otherwise " INV-001 "
        // could be saved with the padding while a later check for "INV-001"
        // (already trimmed) never finds it. Normalizing twice must be a no-op.
        $normalized = DuplicateInvoiceChecker::normalizeNumber(' INV-001 ');

        $this->assertSame($normalized, DuplicateInvoiceChecker::normalizeNumber($normalized));
    }

    public function test_build_response_flags_duplicate_when_already_used(): void
    {
        $response = DuplicateInvoiceChecker::buildResponse('INV-001', true);

        $this->assertTrue($response['duplicate']);
        $this->assertSame('DUPLICATE_INVOICE_NUMBER', $response['code']);
        $this->assertStringContainsString('INV-001', $response['message']);
    }

    public function test_build_response_reports_ok_when_not_used(): void
    {
        $response = DuplicateInvoiceChecker::buildResponse('INV-002', false);

        $this->assertFalse($response['duplicate']);
        $this->assertSame('OK', $response['code']);
    }

    public function test_normalize_numbers_trims_dedupes_and_drops_empty_values(): void
    {
        $numbers = DuplicateInvoiceChecker::normalizeNumbers([' INV-001 ', 'INV-001', '', null, 'INV-002'], null);

        $this->assertSame(['INV-001', 'INV-002'], $numbers);
    }

    public function test_normalize_numbers_falls_back_to_single_number_when_no_array_given(): void
    {
        $numbers = DuplicateInvoiceChecker::normalizeNumbers(null, ' INV-003 ');

        $this->assertSame(['INV-003'], $numbers);
    }

    public function test_normalize_numbers_returns_empty_array_when_nothing_usable_is_given(): void
    {
        $this->assertSame([], DuplicateInvoiceChecker::normalizeNumbers(null, null));
        $this->assertSame([], DuplicateInvoiceChecker::normalizeNumbers([], ''));
    }

    public function test_build_batch_response_flags_duplicate_when_any_number_overlaps(): void
    {
        $response = DuplicateInvoiceChecker::buildBatchResponse(['INV-001', 'INV-002'], ['INV-002']);

        $this->assertTrue($response['duplicate']);
        $this->assertSame('DUPLICATE_INVOICE_NUMBER', $response['code']);
        $this->assertSame(['INV-002'], $response['duplicate_numbers']);
        $this->assertStringContainsString('INV-002', $response['message']);
    }

    public function test_build_batch_response_reports_ok_when_no_overlap(): void
    {
        $response = DuplicateInvoiceChecker::buildBatchResponse(['INV-003'], ['INV-001']);

        $this->assertFalse($response['duplicate']);
        $this->assertSame('OK', $response['code']);
        $this->assertSame([], $response['duplicate_numbers']);
    }
}
