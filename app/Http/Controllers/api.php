<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
// Route::post('login', 'Auth\ApiloginController@login');
// Route::group(['middleware' => 'auth:api'], function(){
// 	Route::get('details', 'Auth\ApiloginController@details');
// });
// Auth Endpoints
// Auth Endpoints
Route::group([
    'prefix' => 'v1/auth'
], function ($router) {
    Route::post('login', 'Auth\ApiloginController@login');
    Route::post('logout', 'Auth\LogoutController@logout');
    Route::post('register', 'Auth\RegisterController@register');
    Route::post('forgot-password', 'Auth\ForgotPasswordController@email');
    Route::post('password-reset', 'Auth\ResetPasswordController@reset');
});


Route::group(['middleware' => 'auth:api'], function(){
	Route::apiResource('todo', 'ApipengajuanController');
	Route::get('details', 'Auth\ApiloginController@details');
});
// Route::group([
//     'middleware' => 'api',
//     'prefix' => 'v1'
// ], function ($router) {
//     Route::post('login', 'Auth\ApiloginController@login');
//     Route::get('logout', 'Auth\LogoutController@logout');
//     Route::post('register', 'Auth\RegisterController@register');
//     Route::post('forgot-password', 'Auth\ForgotPasswordController@email');
//     Route::post('password-reset', 'Auth\ResetPasswordController@reset');
//
//
// });

// Not Found
// Route::fallback(function(){
//     return response()->json(['message' => 'Resource not found.'], 404);
// });
