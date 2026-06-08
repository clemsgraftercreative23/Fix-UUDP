<?php

namespace Tests\Unit;

use App\Support\FonnteMessenger;
use Tests\TestCase;

class FonnteMessengerTest extends TestCase
{
    public function test_normalizes_leading_zero_to_62()
    {
        $this->assertSame('6287877614191', FonnteMessenger::normalizePhone('087877614191'));
    }

    public function test_keeps_62_prefix()
    {
        $this->assertSame('6281908962866', FonnteMessenger::normalizePhone('6281908962866'));
    }

    public function test_strips_non_digits()
    {
        $this->assertSame('6287877614191', FonnteMessenger::normalizePhone('+62 878-7761-4191'));
    }

    public function test_returns_null_for_empty()
    {
        $this->assertNull(FonnteMessenger::normalizePhone(null));
        $this->assertNull(FonnteMessenger::normalizePhone(''));
    }
}
