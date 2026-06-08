<?php

namespace App\Services\ApprovalReminder;

use App\Support\FonnteMessenger;

class FonnteClient
{
    public function send(string $target, string $message): array
    {
        $result = FonnteMessenger::send($target, $message, ['channel' => 'approval_reminder']);

        if ($result === null) {
            throw new \RuntimeException('Fonnte send failed or phone number is invalid');
        }

        return $result;
    }
}