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
        $this->middleware('auth');
    }
    
    public function index(Request $request)
    {
        return view('test');
    }
    
}
