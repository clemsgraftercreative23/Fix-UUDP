<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\APIController;
use App\Http\Resources\TodoCollection;
use App\Http\Resources\TodoResource;
use App\Pengajuan;
use Auth;
use DB;

class ApipengajuanController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get user from $request token.
        if (! $user = auth()->setRequest($request)->user()) {
            return $this->responseUnauthorized();
        }
        $id_user = Auth::user()->id;

        $collection = Pengajuan::join('master_project','pengajuan.id_project','master_project.id')
        ->select('pengajuan.*','master_project.nama','master_project.no_project','master_project.keterangan')
        ->where('pengajuan.id_user', $id_user);


        $collection = $collection->latest()->paginate();

        return new TodoCollection($collection);
    }


}
