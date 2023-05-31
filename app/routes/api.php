<?php

use App\Http\Controllers\MultipleDatabaseController;
use App\Http\Controllers\SingleDatabaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('test', function () {
    return response()->json([
        'status'  => true,
        'message' => 'success'
    ]);
});

Route::group(['prefix' => 'single'], function () {
    Route::post('create-order', [SingleDatabaseController::class, 'createOrder']);
    Route::get('get-orders', [SingleDatabaseController::class, 'getAllOrders']);
});

Route::group(['prefix' => 'multiple'], function () {
    Route::post('create-order', [MultipleDatabaseController::class, 'createOrder']);
    Route::get('get-orders', [MultipleDatabaseController::class, 'getAllOrders']);
});