<?php

namespace App\Support;

/**
 * Pulls the Accurate-assigned record reference (id/number) out of a
 * successful other-payment/save.do response body, e.g.
 * {"s":true,"d":{"id":123,"number":"OP-00045"}}.
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
