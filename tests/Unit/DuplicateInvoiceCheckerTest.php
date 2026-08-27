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
}
