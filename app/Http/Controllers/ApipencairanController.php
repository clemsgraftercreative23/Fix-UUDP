<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\APIController;
use App\Http\Resources\PencairanCollection;
use App\Http\Resources\PencairanResource;
use App\Pengajuan;
use Auth;
use DB;

class ApipencairanController extends ApiController
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
        ->where('pengajuan.sisa_pengajuan', 0);

        $collection = $collection->latest()->paginate();

        return new PencairanCollection($collection);
    }


}
