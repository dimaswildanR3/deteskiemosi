<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SessionController;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('api')->group(function () {

    Route::post('/session/start', [SessionController::class, 'start']);

    // 🔥 SATU ENDPOINT UNTUK SEMUA DETEKSI
    Route::post('/store', [SessionController::class, 'store']);
});
Route::post('/session/start', [SessionController::class, 'start']);
Route::post('/store', [SessionController::class, 'store']);
Route::post('/session/stop', [SessionController::class, 'stop']);