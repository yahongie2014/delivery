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

Route::group(['middleware' => ['cors']], function () {
    Route::post('login', 'UserController@apiLogin');
    Route::group(['prefix' => 'v1', 'middleware' => 'auth:api'], function () {
        Route::group(['middleware' => ['setlanguage']], function () {

            Route::group(['prefix' => 'provider', 'middleware' => 'isproviderapi'], function () {
                Route::resource('orders', 'OrderController', ['except' => ['create','edit']]);
                Route::post('orders/cancel', 'OrderController@providerCancelOrder');
                Route::resource('categories','CategoryController',['only' => ['index']]);
                Route::resource('languages','LanguageController',['only' => ['index']]);
                Route::resource('cities','CityController',['only' => ['index']]);
                Route::resource('services','ServiceTypeController',['only' => ['index']]);
                Route::resource('paytypes','PaymentTypeController',['only' => ['index']]);
            });
        });
    });
});
//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});
