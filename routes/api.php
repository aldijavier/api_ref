<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ApiController;


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

Route::middleware('api')->group(function() {
    Route::get('/rad-alluser', [ApiController::class, 'UserInfo']);
    Route::post('/rad-findusername', [ApiController::class, 'FindByUsername']);
    Route::post('/rad-block', [ApiController::class, 'BlockUserConnection']);
    Route::post('/rad-unblock', [ApiController::class, 'UnblockUserConnection']);
    Route::post('/rad-checkblock', [ApiController::class, 'CheckBlock']);
    Route::post('/rad-checkradusergroup', [ApiController::class, 'CheckRadusergroupUser']);
    Route::get('/rad-groupstatus/all', [ApiController::class, 'groupStatusAll']);
    Route::post('/rad-groupstatus/search', [ApiController::class, 'groupStatusSearch']);
});



Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
