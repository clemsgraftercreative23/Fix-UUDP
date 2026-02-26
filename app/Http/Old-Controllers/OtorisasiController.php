<?php

namespace App\Http\Controllers;

use App\Module_event;
use App\Module_gallery;
use Illuminate\Http\Request;
use Validator;
use DataTables;
use Auth;
use DB;

class OtorisasiController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
    	if (Auth::user()->jabatan=='superadmin') {
    		return view('otorisasi.otorisasi');
    	} else{
    		return view('home');
    	}
        
    }
    
}
