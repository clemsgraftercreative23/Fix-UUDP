<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\APIController;

use App\Pengajuan;
use Auth;
use DB;

class TodoController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get user from $request token.
        // if (! $user = auth()->setRequest($request)->user()) {
        //     return $this->responseUnauthorized();
        // }
        $id_user = Auth::user()->id;

        $collection = Pengajuan::where('id_user', $id_user);


        $collection = $collection->latest()->paginate();
        // dd($collection);
        return response()->json(['data' => $collection]);

        // return new TodoCollection($collection);
    }


}
