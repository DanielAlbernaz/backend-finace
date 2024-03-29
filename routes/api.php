<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FinancialReleaseController;
use App\Http\Controllers\RevenueController;
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

Route::controller(AuthController::class)
    ->prefix('auth')
    ->group(function() {
        Route::post('login', 'login');
        Route::post('logout', 'logout');
        Route::post('register','register');
        Route::post('logout', 'logout');
        // Route::get('me', 'me');
});



Route::group(
    [
        'middleware' => ['auth:sanctum'],
    ],
    function () {

        Route::get('auth/me', [AuthController::class, 'me']);

        Route::apiResource('financial_release', FinancialReleaseController::class);

    });
