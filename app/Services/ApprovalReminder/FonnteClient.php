<?php

namespace App\Services\ApprovalReminder;

use Illuminate\Support\Facades\Log;

class FonnteClient
{
    public function send(string $target, string $message): array
    {
        $baseUrl = config('services.fonnte.base_url', 'https://api.fonnte.com/send');
        $token = config('services.fonnte.token');

        if (empty($token)) {
            throw new \RuntimeException('Fonnte token is not configured');
        }

        $response = \Curl::to($baseUrl)
            ->withHeaders([
                'Authorization: ' . $token,
            ])
            ->withData([
                'target' => $target,
                'message' => $message,
            ])
            ->post();

        $decoded = json_decode((string) $response, true);

        if (is_array($decoded) && array_key_exists('status', $decoded) && !$decoded['status']) {
            Log::warning('Fonnte reminder send failed', [
                'target' => $target,
                'response' => $decoded,
            ]);
        }

        return is_array($decoded) ? $decoded : ['raw' => (string) $response];
    }
}