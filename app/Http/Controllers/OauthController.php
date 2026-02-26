<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use App\Api;
class OauthController extends Controller
{
    //
    function accurateAuthorize(Request $request) {
        $authorizationUrl = 'https://account.accurate.id/oauth/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id' => 'f6e23293-8fab-4bf4-aaab-c7d4ff108da5',
            'redirect_uri' => url('/').'/accurate-callback',
            'scope' => 'employee_delete employee_view employee_save journal_voucher_save journal_voucher_delete journal_voucher_view glaccount_save glaccount_delete glaccount_view department_save department_delete department_view project_save project_delete project_view',
        ]);
        return redirect($authorizationUrl);
    }

    function callback(Request $request) {
        $code = $request->code;
        
        //GET ACCESS TOKEN
        $result = Curl::to("https://account.accurate.id/oauth/token")
                    ->withHeaders([
                        'Content-Type: application/x-www-form-urlencoded',
                        'Accept: application/json',
                        'Authorization: Basic ZjZlMjMyOTMtOGZhYi00YmY0LWFhYWItYzdkNGZmMTA4ZGE1OjgwZDA1MjhmOWNiODdhMjc0MjkzYWJmOWQyZmIzNTI4'
                    ])
                    ->withData([
                        'code' => $code,
                        'grant_type' => 'authorization_code',
                        'redirect_uri' => url('/').'/accurate-callback'
                    ])->post();
        $result = json_decode($result, TRUE);
        $token =  $result['access_token'];
        $dataDb = Curl::to("https://account.accurate.id/api/open-db.do?id=838508")
                ->withHeaders([
                    // 'Content-Type: application/x-www-form-urlencoded',
                    // 'Accept: application/json',
                    'Authorization: Bearer '.$token
                ])->get();
        $dataDb = json_decode($dataDb, TRUE);
    

        $session = $dataDb['session'];

        $data = \DB::table('apis')
            ->where('id', 1)
            ->update([
                'token' => $token,
                'session' => $session
            ]);
        $data = Api::find(1);
        
        return "<script>window.close();</script>";
        // return response()->json($data);
        // return redirect('/');

    }
}
