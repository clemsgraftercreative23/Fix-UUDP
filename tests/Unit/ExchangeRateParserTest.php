<?php

namespace Tests\Unit;

use App\Support\ExchangeRateParser;
use Tests\TestCase;

class ExchangeRateParserTest extends TestCase
{
    public function test_parses_indonesian_thousands_dot_notation_as_integer_rate(): void
    {
        $this->assertSame(17883.0, ExchangeRateParser::parseFloat('17.883'));
        $this->assertSame(17883.0, ExchangeRateParser::parseFloat('17.883,00'));
        $this->assertSame('17883.00', ExchangeRateParser::normalizeForStorage('17.883'));
    }

    public function test_parses_comma_decimal_notation(): void
    {
        $this->assertSame(12.89, ExchangeRateParser::parseFloat('12,89'));
        $this->assertSame('12.89', ExchangeRateParser::normalizeForStorage('12,89'));
    }

    public function test_parses_plain_integer_rates(): void
    {
        $this->assertSame(115.0, ExchangeRateParser::parseFloat('115'));
        $this->assertSame(17883.0, ExchangeRateParser::parseFloat('17883'));
    }

    public function test_allowance_example_usd_rate(): void
    {
        $allowanceUsd = 54;
        $rate = ExchangeRateParser::parseFloat('17.883');
        $this->assertSame(965682.0, $allowanceUsd * $rate);
    }
}
