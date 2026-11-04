<?php

namespace App\Support;

/**
 * Detects a form POST that PHP cut short because it carried more fields than
 * max_input_vars (default 1000).
 *
 * PHP keeps exactly max_input_vars variables, throws the rest away and only
 * logs a warning -- the request itself carries on as if nothing happened.
 * The travel form posts ~14 fields per day plus ~8 per expense row plus 2 per
 * evidence file, so a long trip (Nov 2026: 23 days) lands right on the limit.
 * Because the SUBMIT / DRAFT button is the last field in the form it is the
 * first thing lost, and the controller then either sees no recognisable action
 * or quietly validates and saves a half-empty submission.
 *
 * A request that arrives holding exactly the limit has therefore been
 * truncated (a genuine form that lands on the limit to the digit is not a
 * realistic case, and rejecting it is the safe direction).
 */
class TruncatedFormInput
{
    /** Number of scalar fields in a parsed POST body, nested arrays counted leaf by leaf the way PHP counts them. */
    public static function countFields(array $post): int
    {
        $count = 0;
        array_walk_recursive($post, function () use (&$count) {
            $count++;
        });

        return $count;
    }

    /** True when $post holds as many fields as $limit allows, i.e. PHP stopped reading at the limit. A limit of 0 or less means "unlimited". */
    public static function wasTruncated(array $post, int $limit): bool
    {
        return $limit > 0 && self::countFields($post) >= $limit;
    }

    public static function message(int $limit): string
    {
        return 'This submission has too many form fields (the server reads at most ' . $limit
            . ' per save), so part of it never arrived and nothing was saved. '
            . 'Your data is still on this page. Reduce the number of days, expense rows or files, '
            . 'save as DRAFT, then add the rest from the draft with ADD DAYS / the edit form.';
    }
}
