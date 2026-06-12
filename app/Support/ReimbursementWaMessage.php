<?php

namespace App\Support;

class ReimbursementWaMessage
{
    public static function waitingVerificationBy(string $role): string
    {
        return 'Saat ini sedang menunggu Proses Verifikasi oleh ' . $role . '.';
    }

    public static function waitingYourVerification(string $role): string
    {
        return 'Saat ini sedang menunggu Proses Verifikasi Anda (' . $role . ').';
    }
}
