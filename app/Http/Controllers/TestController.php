<?php

namespace App\Http\Controllers;

use App\Module_event;
use App\Module_gallery;
use Illuminate\Http\Request;
use Validator;
use DataTables;
use Auth;
use DB;

class TestController extends Controller
{

    public function __construct()
    {
        $this->middleware('guest');
    }
    
    public function index(Request $request)
    {
        return view('test');
    }

    function accurateAuthorize(Request $request) {
        $authorizationUrl = 'https://account.accurate.id/oauth/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id' => '7c02b139-49cd-4a58-ba32-e76b9202062f',
            'redirect_uri' => 'http://127.0.0.1:8001/test',
            'scope' => 'item_view',
        ]);
        return redirect($authorizationUrl);
        //  dd($authorizationUrl);
        // Return the view that triggers the JavaScript to open the popup
        return view('popup', ['authorizationUrl' => $authorizationUrl]);
    
    }
    
}
