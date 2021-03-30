<?php

use App\Http\Controllers\PointController;
use App\Http\Controllers\ReservationController;
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

Route::middleware(['cors', 'auth.simple'])->group(function () {
    Route::get('/points', [PointController::class, 'index']);
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'create']);
    Route::post('/reservations/{reservation}/answer', [ReservationController::class, 'answer']);
});
