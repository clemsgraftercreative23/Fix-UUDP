<?php

$code = $_GET['code'];
//$code = 'hUJ4IyL1VrCxwriAKCNL';

//GET ACCESS TOKEN
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"https://account.accurate.id/oauth/token");
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
'Content-Type: application/x-www-form-urlencoded',
'Accept: application/json',
'Authorization: Basic N2MwMmIxMzktNDljZC00YTU4LWJhMzItZTc2YjkyMDIwNjJmOjY1M2E4YTJiZGE1OTJhYzM2MmE2NzQ0ZTliMjNhZjExCg=='));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
            "code=".$code."&grant_type=authorization_code&redirect_uri=".url('/')."/test");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec ($ch);
curl_close ($ch);
$data = json_decode($server_output, TRUE);
// $token =  $data['access_token'];
dd($data);




//GET SESSION 
$api_url = 'https://account.accurate.id/api/open-db.do?id=239197';
$context = stream_context_create(array(
    'http' => array(
        'header' => "Authorization: Bearer".$token,
    ),
));
$result = file_get_contents($api_url, false, $context);
$data = json_decode($result, TRUE);
$session = $data['session'];

\App\Api::find(1)->update(['token' => $token, 'session' => $data['session']]);

?>
