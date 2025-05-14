<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PermissionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']) -> middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// autentikasi
Route::middleware('auth:sanctum') -> group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('tokens', [AuthController::class, 'activeTokens']);
});

Route::middleware('auth:sanctum') -> group(function () {
    Route::post('/leaves', [LeaveController::class, 'store']);
    Route::post('/permissions', [PermissionController::class, 'store']);
    Route::get('/attendances', [AttendanceController::class, 'index']);
    Route::get('/attendances/today', [AttendanceController::class, 'statusToday']);
});