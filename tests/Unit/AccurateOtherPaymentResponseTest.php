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

    public function test_returns_nulls_when_d_is_a_list_not_an_object(): void
    {
        // Accurate's list.do responses shape "d" as an array of records;
        // save.do responses shape it as a single object. Guard against
        // accidentally treating the wrong shape as a record.
        $reference = AccurateOtherPaymentResponse::extractRecordReference('{"s":true,"d":[{"id":1}]}');

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
