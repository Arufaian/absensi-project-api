<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PermissionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::post('/register', [AuthController::class, 'register']) -> middleware('throttle:register');
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

// absensi
Route::middleware('auth:sanctum') -> group(function () {
    Route::get('/attendances', [AttendanceController::class, 'index']);
    Route::get('/attendances/today', [AttendanceController::class, 'statusToday']);
});

// cuti
Route::middleware('auth:sanctum') -> group(function () {
    Route::post('/leaves', [LeaveController::class, 'store']);
    Route::get('/leaves/status', [LeaveController::class, 'index']);
});


// izin
Route::middleware('auth:sanctum') -> group(function () {
    Route::post('/permissions', [PermissionController::class, 'store']);
    Route::get('/permissions/status', [PermissionController::class, 'index']);
});


// modul admin
Route::middleware('auth:sanctum') -> group( function () {
    Route::patch('leaves/{user_id}/approve', [AdminController::class, 'approveLeave']);
    Route::patch('permissions/{user_id}/approve', [AdminController::class, 'approvePermission']);
    Route::post('overtimes', [AdminController::class, 'storeOvertime']);
    Route::patch('/salaries/{id}', [AdminController::class, 'updateSalary']);
    Route::get('/attendances/all', [AdminController::class, 'allAttendances']);
});

// membuat user
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users', [UserController::class, 'store']); // hanya admin/owner
});


// absensi menggunakan rfid
Route::post('/attendance/rfid/check-in', [AttendanceController::class, 'rfidCheckIn']);
Route::post('/attendance/rfid/check-out', [AttendanceController::class, 'rfidCheckOut']);
Route::get('/attendance/rfid/status', [AttendanceController::class, 'showStatusAttendance']);