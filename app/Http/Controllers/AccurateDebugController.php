<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Accurate\AccurateApiTokenClient;

class AccurateDebugController extends Controller
{
    public function signatureTest(Request $request)
    {
        if (env('APP_ENV') === 'production') {
            abort(403, 'Not allowed in production');
        }

        $client = new AccurateApiTokenClient();

        $method = strtoupper($request->query('method', 'POST'));
        $path = $request->query('path', '/accurate/api/journal-voucher/insert.do');
        $timestamp = $request->query('timestamp', $client->buildTimestampString());
        $rawBody = $request->input('body', '');

        $modes = array_values(array_unique(array_filter([
            'TIMESTAMP_ONLY',
            $client->getSignMode(),
            'APPLICATION_METHOD_PATH_TIMESTAMP_BODY',
            'APPLICATION_TIMESTAMP_METHOD_PATH_BODY',
            'METHOD_PATH_TIMESTAMP_BODY',
            'TIMESTAMP_METHOD_PATH_BODY',
        ])));

        $keys = $client->debugSigningKeys();

        $attempts = [];
        foreach ($modes as $mode) {
            // set mode for the buildSignString call
            $client->signMode = $mode;
            foreach ($keys as $k) {
                $signString = $client->debugBuildSignString($method, $path, $timestamp, $rawBody);
                $signature = $client->buildSignature($method, $path, $timestamp, $rawBody, $k);
                $attempts[] = [
                    'mode' => $mode,
                    'key_mask' => $client->debugMaskKey($k),
                    'signature' => $signature,
                    'sign_string' => $signString,
                ];
            }
        }

        return response()->json([
            'applicationName' => $client->getApplicationName(),
            'timestamp' => $timestamp,
            'attempts' => $attempts,
        ]);
    }
}
