<?php

namespace App\Support;

/**
 * Pulls the Accurate-assigned record reference (id/number) out of a
 * successful other-payment/save.do response body.
 *
 * Confirmed against production (reimbursement #1546, synced 2026-09-02):
 * save.do wraps the created record in a list just like list.do does, e.g.
 * {"s":true,"d":[{"id":76854,"number":"001/SMBC-IDR/09/2026"}]} -- NOT a
 * bare object. Only detail.do (fetch-by-id) returns "d" as a bare object.
 * Handle both shapes so this doesn't depend on which one is really in play.
 *
 * This reference is what a later "reverse" action needs to call
 * other-payment/delete.do?id=... (or ?number=... as Accurate's documented
 * alternative) -- without it, a synced record can never be programmatically
 * reversed again.
 */
class AccurateOtherPaymentResponse
{
    /** @return array{id: ?string, no: ?string} */
    public static function extractRecordReference(?string $rawResponseBody): array
    {
        $decoded = json_decode((string) $rawResponseBody, true);
        $record = is_array($decoded) ? ($decoded['d'] ?? null) : null;

        if (is_array($record) && array_key_exists(0, $record) && is_array($record[0])) {
            $record = $record[0];
        }

        if (!is_array($record)) {
            return ['id' => null, 'no' => null];
        }

        return [
            'id' => self::stringOrNull($record['id'] ?? null),
            'no' => self::stringOrNull($record['number'] ?? null),
        ];
    }

    private static function stringOrNull($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_scalar($value) ? (string) $value : null;
    }
}
