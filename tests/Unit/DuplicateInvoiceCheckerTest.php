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

    // -- Line-level (invoice + date + amount) helpers --------------------
    // These back the "one Bill No can span several days" case: a hotel
    // Guest Folio (e.g. Bill No 394159/11) can list "Room Charge" on 3, 4
    // and 5 Aug under the same Bill No -- flagging every reuse of that
    // number as a duplicate would wrongly reject the 2nd/3rd day's valid
    // row, so the line-level check also requires the date and amount to
    // match before calling it a duplicate.

    public function test_is_complete_line_true_when_number_date_and_amount_all_present(): void
    {
        $this->assertTrue(DuplicateInvoiceChecker::isCompleteLine('394159/11', '2026-08-04', '700000'));
        $this->assertTrue(DuplicateInvoiceChecker::isCompleteLine('394159/11', '2026-08-04', 700000));
        $this->assertTrue(DuplicateInvoiceChecker::isCompleteLine('394159/11', '2026-08-04', 0));
    }

    public function test_is_complete_line_false_when_number_is_blank(): void
    {
        $this->assertFalse(DuplicateInvoiceChecker::isCompleteLine('   ', '2026-08-04', '700000'));
    }

    public function test_is_complete_line_false_when_date_is_blank(): void
    {
        $this->assertFalse(DuplicateInvoiceChecker::isCompleteLine('394159/11', '', '700000'));
    }

    public function test_is_complete_line_false_when_amount_is_not_numeric(): void
    {
        $this->assertFalse(DuplicateInvoiceChecker::isCompleteLine('394159/11', '2026-08-04', ''));
        $this->assertFalse(DuplicateInvoiceChecker::isCompleteLine('394159/11', '2026-08-04', 'abc'));
        $this->assertFalse(DuplicateInvoiceChecker::isCompleteLine('394159/11', '2026-08-04', null));
    }

    public function test_normalize_amount_rounds_to_two_decimals(): void
    {
        $this->assertSame(700000.0, DuplicateInvoiceChecker::normalizeAmount('700000'));
        $this->assertSame(700000.12, DuplicateInvoiceChecker::normalizeAmount('700000.1234'));
    }

    public function test_amounts_match_compares_numerically_ignoring_type_and_formatting(): void
    {
        // Travel's amount column is decimal, Entertainment's is a numeric
        // string (ExchangeRateParser::normalizeForStorage()) -- both must
        // compare equal to the same logical amount.
        $this->assertTrue(DuplicateInvoiceChecker::amountsMatch('700000.00', 700000));
        $this->assertTrue(DuplicateInvoiceChecker::amountsMatch(700000, '700000'));
        $this->assertFalse(DuplicateInvoiceChecker::amountsMatch(700000, 2100000));
    }

    public function test_amounts_match_false_when_either_side_is_not_numeric(): void
    {
        $this->assertFalse(DuplicateInvoiceChecker::amountsMatch('', 700000));
        $this->assertFalse(DuplicateInvoiceChecker::amountsMatch(700000, null));
        $this->assertFalse(DuplicateInvoiceChecker::amountsMatch('abc', 700000));
    }

    public function test_amounts_match_distinguishes_same_day_different_lines_on_the_same_invoice(): void
    {
        // Grand Artos Guest Folio Bill No 394159/11, 05 Aug: "BNI EDC"
        // -2,100,000 and "Room Charge" 700,000 -- same invoice, same date,
        // different amount. Only the amount tells these two real lines apart.
        $this->assertFalse(DuplicateInvoiceChecker::amountsMatch(-2100000, 700000));
    }
}
