<?php

namespace Tests\Unit;

use App\Support\TruncatedFormInput;
use PHPUnit\Framework\TestCase;

/**
 * Nov 2026: a 23-day travel submission came back to a blank form. PHP drops
 * every field past max_input_vars silently -- including the SUBMIT button,
 * which is the last field -- so the save was never recognised.
 */
class TruncatedFormInputTest extends TestCase
{
    private function post(int $fields): array
    {
        $post = ['_token' => 'abc'];
        $i = 0;
        while (TruncatedFormInput::countFields($post) < $fields) {
            $post['reimburse'][intdiv($i, 3)]['f' . ($i % 3)] = 'x';
            $i++;
        }

        return $post;
    }

    public function test_counts_nested_fields_leaf_by_leaf(): void
    {
        $post = ['_token' => 't', 'reimburse' => [['date' => 'd', 'detail' => [['amount' => '1', 'tax' => '0']]], ['date' => 'e']], 'rates' => [['code' => 'USD', 'rate' => '1']]];

        $this->assertSame(7,TruncatedFormInput::countFields($post));
    }

    public function test_request_below_the_limit_is_not_truncated(): void
    {
        $this->assertFalse(TruncatedFormInput::wasTruncated($this->post(999), 1000));
    }

    public function test_request_at_the_limit_is_truncated(): void
    {
        $this->assertTrue(TruncatedFormInput::wasTruncated($this->post(1000), 1000));
    }

    public function test_unlimited_input_vars_never_flags(): void
    {
        $this->assertFalse(TruncatedFormInput::wasTruncated($this->post(5000), 0));
    }

    public function test_message_names_the_limit(): void
    {
        $this->assertStringContainsString('1000', TruncatedFormInput::message(1000));
    }
}
