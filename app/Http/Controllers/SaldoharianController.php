<?php

namespace App\Http\Controllers;


use App\Saldoharian;
use App\Api;
use Illuminate\Http\Request;
use Validator;
use DataTables;
use Auth;
use DB;
use Hash;

class SaldoharianController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index () {
    	$saldo = DB::select( DB::raw("SELECT * FROM saldoharian"));
    	$bank = DB::select( DB::raw("SELECT * FROM listkasbank WHERE kode_list='11200'"));
        return view('saldo.index',['saldo'=>$saldo,'bank'=>$bank]);
    }

    public function store(Request $request)
    {
        $rules = array(
            'tanggal'    =>  'required',
            'saldo'     =>  'required',
            'bank'     =>  'required',
        );

        $error = Validator::make($request->all(), $rules);

        if($error->fails())
        {
            return response()->json(['errors' => $error->errors()->all()]);
        }

        $tgl = date("Y-m-d", strtotime($request->tanggal));

        $form_data = array(
            'tanggal'        =>  $tgl,
            'saldo'         =>  $request->saldo,
            'bank'         =>  $request->bank,
            'created_by'         => Auth::user()->username,
            'updated_by'         =>  "-"
        );

        Saldoharian::create($form_data);
        return response()->json(['success' => 'Data Added successfully.']);
    }

    public function destroy($id)
    {
        $data = Saldoharian::findOrFail($id);
        $data->delete();
        return response()->json(['success' => 'Data Deleted successfully.']);
    }
    
}
