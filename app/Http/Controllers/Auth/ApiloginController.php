<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Http\Controllers\APIController;


class ApiloginController extends APIController
{

    public $successStatus = 200;

    public function login(){
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){
            $user = Auth::user();
            $success =  $user->createToken('nApp')->accessToken;
            // return response()->json(['success' => $success], $this->successStatus);
            return response()->json([
                       'status' => 200,
                       'message' => 'Authorized.',
                       'access_token' => $success,
                       'token_type' => 'bearer',
                       'user' => array(
                           'id' => $user->id,
                           'name' => $user->name
                       )
                   ], 200);
        }
        else{
            return response()->json(['error'=>'Unauthorised'], 401);
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('nApp')->accessToken;
        $success['name'] =  $user->name;

        return response()->json(['success'=>$success], $this->successStatus);
    }

    public function details(Request $request)
    {
      if (! $user = auth()->setRequest($request)->user()) {
          return $this->responseUnauthorized();
      }
        $user = Auth::user();
        return response()->json(['success' => $user], $this->successStatus);
    }
}
