<?php

namespace Tests\Unit;

use App\Support\AccurateOtherPaymentResponse;
use Tests\TestCase;

class AccurateOtherPaymentResponseTest extends TestCase
{
    public function test_extracts_id_and_number_from_a_successful_response(): void
    {
        $reference = AccurateOtherPaymentResponse::extractRecordReference('{"s":true,"d":{"id":123,"number":"OP-00045"}}');

        $this->assertSame('123', $reference['id']);
        $this->assertSame('OP-00045', $reference['no']);
    }

    public function test_returns_nulls_when_d_is_missing(): void
    {
        $reference = AccurateOtherPaymentResponse::extractRecordReference('{"s":true}');

        $this->assertNull($reference['id']);
        $this->assertNull($reference['no']);
    }

    public function test_extracts_from_the_first_element_when_d_is_a_list(): void
    {
        // Confirmed against production: other-payment/save.do wraps the
        // created record in a list, same as list.do -- not a bare object.
        $reference = AccurateOtherPaymentResponse::extractRecordReference('{"s":true,"d":[{"id":1,"number":"OP-1"}]}');

        $this->assertSame('1', $reference['id']);
        $this->assertSame('OP-1', $reference['no']);
    }

    public function test_regression_reimbursement_1546_save_response_shape(): void
    {
        // The exact shape confirmed live in Accurate for reimbursement
        // #1546 (synced 2026-09-02) that the original object-only parser
        // silently failed to extract anything from.
        $reference = AccurateOtherPaymentResponse::extractRecordReference(
            '{"s":true,"d":[{"id":76854,"number":"001/SMBC-IDR/09/2026"}]}'
        );

        $this->assertSame('76854', $reference['id']);
        $this->assertSame('001/SMBC-IDR/09/2026', $reference['no']);
    }

    public function test_returns_nulls_when_d_is_an_empty_list(): void
    {
        $reference = AccurateOtherPaymentResponse::extractRecordReference('{"s":true,"d":[]}');

        $this->assertNull($reference['id']);
        $this->assertNull($reference['no']);
    }

    public function test_returns_nulls_for_malformed_json(): void
    {
        $reference = AccurateOtherPaymentResponse::extractRecordReference('not json');

        $this->assertNull($reference['id']);
        $this->assertNull($reference['no']);
    }

    public function test_returns_nulls_for_null_input(): void
    {
        $reference = AccurateOtherPaymentResponse::extractRecordReference(null);

        $this->assertNull($reference['id']);
        $this->assertNull($reference['no']);
    }

    public function test_treats_empty_string_id_as_missing(): void
    {
        $reference = AccurateOtherPaymentResponse::extractRecordReference('{"d":{"id":"","number":"OP-1"}}');

        $this->assertNull($reference['id']);
        $this->assertSame('OP-1', $reference['no']);
    }
}
