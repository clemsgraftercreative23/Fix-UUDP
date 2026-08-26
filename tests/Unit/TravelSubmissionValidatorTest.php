<?php

namespace Tests\Unit;

use App\Support\TravelSubmissionValidator;
use Tests\TestCase;

class TravelSubmissionValidatorTest extends TestCase
{
    private function completeLeg(array $detailOverrides = []): array
    {
        return [
            'date' => '2026-08-20',
            'detail' => [
                array_merge([
                    'cost_type_id' => '3',
                    'destination' => 'Toko ATK',
                    'currency' => 'IDR',
                    'payment_type' => 'Cash',
                    'amount' => '50000',
                ], $detailOverrides),
            ],
        ];
    }

    public function test_no_errors_when_every_detail_row_is_complete(): void
    {
        $errors = TravelSubmissionValidator::findErrors([$this->completeLeg()]);

        $this->assertSame([], $errors);
    }

    public function test_regression_reimbursement_1544_missing_payment_type_is_rejected(): void
    {
        $errors = TravelSubmissionValidator::findErrors([
            $this->completeLeg(['payment_type' => '']),
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Tipe pembayaran', $errors[0]);
        $this->assertStringContainsString('hari ke-1', $errors[0]);
    }

    public function test_rows_without_a_cost_type_are_skipped_not_validated(): void
    {
        $errors = TravelSubmissionValidator::findErrors([
            [
                'detail' => [
                    ['cost_type_id' => '', 'destination' => '', 'currency' => '', 'payment_type' => '', 'amount' => ''],
                ],
            ],
        ]);

        // An empty placeholder row (no cost type picked) isn't a "row to validate" --
        // but the leg still needs at least one real detail row.
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Minimal satu rincian biaya', $errors[0]);
    }

    public function test_empty_legs_array_is_rejected(): void
    {
        $errors = TravelSubmissionValidator::findErrors([]);

        $this->assertSame(['Minimal satu hari perjalanan harus diisi.'], $errors);
    }

    public function test_reports_one_error_per_missing_field_per_row_across_multiple_legs(): void
    {
        $errors = TravelSubmissionValidator::findErrors([
            $this->completeLeg(['payment_type' => '', 'amount' => '']),
            $this->completeLeg(),
        ]);

        $this->assertCount(2, $errors);
        $this->assertStringContainsString('hari ke-1', $errors[0]);
    }
}
