<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Validator;
use DataTables;
use Auth;
use DB;

class UseraplikasiController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index(Request $request)
    {
        $user = DB::select( DB::raw("SELECT *, users.id FROM users LEFT JOIN departemen ON users.departmentId = departemen.id WHERE jabatan='superadmin' OR jabatan='Owner' OR jabatan='Finance' OR jabatan='Direktur Operasional'  OR jabatan='Direktur Utama'" ));  
        return view('user/index',['user'=>$user]);
    }

    public function add_user()
    {
         $user = DB::select( DB::raw("SELECT * FROM users" ));
         $departemen = DB::select( DB::raw("SELECT * FROM departemen ORDER BY nama_departemen ASC" ));
         
        return view('user/add_user',['user'=>$user, 'departemen' => $departemen]);
    }

    public function edit_useraplikasi($id)
    {
        $us = DB::select( DB::raw("SELECT * FROM users WHERE id='$id'" ));  
        $user = DB::select( DB::raw("SELECT * FROM users" ));  
        $departemen = DB::select( DB::raw("SELECT * FROM departemen ORDER BY nama_departemen ASC" ));
        
        return view('user/edit_user',['user'=>$user, 'us'=>$us, 'departemen' => $departemen]);
    }
    

    public function edit($id)
    {
        if(request()->ajax())
        {
            $data = User::findOrFail($id);
            return response()->json(['data' => $data]);
        }
    }

    public function update(Request $request)
    {

        $form_data = array(
            'jabatan'        =>  $request->jabatan,
            'departmentId'        =>  $request->departmentId,
        );

        User::whereId($request->hidden_id)->update($form_data);
        return response()->json(['success' => 'Data is successfully updated']);
    }

    public function remove_jabatan(Request $request)
    {

        $form_data = array(
            'jabatan'        =>  '-',
        );

        User::whereId($request->hidden_id)->update($form_data);
        return response()->json(['success' => 'Data is successfully updated']);
    }

    
   

    public function fillEmployee($id)
    {
         $qry = DB::table('users')->where('username',$id)->pluck('id'); 
         $id_user = $qry['0'];

        if(request()->ajax())
        {
            $data = User::findOrFail($id_user);
            return response()->json(['data' => $data]);
        }
    }

}
