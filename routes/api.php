<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveQuotaController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SalaryController;
use App\Models\LeaveQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::post('/register', [AuthController::class, 'register']) -> middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/attendances/missing', [AttendanceController::class, 'usersNotCheckedInToday']);
Route::post('/attendances/mark-alpa', [AttendanceController::class, 'markAbsentAsAlpa']);



// autentikasi
Route::middleware('auth:sanctum') -> group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('logout-all', [AuthController::class, 'logoutAll']);
    Route::get('tokens', [AuthController::class, 'activeTokens']);
});

// route user
Route::middleware('auth:sanctum') -> group(function() {
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/me', [AuthController::class, 'update']);

    Route::get('/me/leaves', [LeaveController::class, 'index']);
    Route::get('/me/permission', [PermissionController::class, 'index']);
    Route::get('/me/attendances', [AttendanceController::class, 'index']);
    Route::get('/me/get-status-attendance', [AttendanceController::class, 'statusToday']);
    route::get('/me/get-monthly-attendance', [AttendanceController::class, 'monthlyAttendance']);


    Route::get('/me/leave-quota', [LeaveQuotaController::class, 'index']);

});



// absensi
Route::middleware('auth:sanctum') -> group(function () {
    Route::get('/attendances', [AttendanceController::class, 'index']);
    Route::get('/attendances/today', [AttendanceController::class, 'statusToday']);
    Route::get('/attendances/all', [AttendanceController::class, 'getAllAttendance']);
});
Route::get('/attendances/get-daily-attendance', [AttendanceController::class, 'getDailyAttendance']);
Route::get('/attendances/monthly', [AttendanceController::class, 'getMonthlyAttendance']);


// cuti
Route::middleware('auth:sanctum') -> group(function () {
    Route::post('/leaves', [LeaveController::class, 'store']);
    Route::get('/leaves', [AdminController::class, 'getAllLeavesBasedOnRole']);
});


// izin
Route::middleware('auth:sanctum') -> group(function () {
    Route::post('/permissions', [PermissionController::class, 'store']);
    Route::get('/permissions', [AdminController::class, 'getAllPermissionsBasedOnRole']);
});


// modul admin dan hrd
Route::middleware('auth:sanctum') -> group( function () {
    Route::patch('leaves/{user_id}/approve', [AdminController::class, 'approveLeave']);
    Route::patch('leaves/{user_id}/reject', [AdminController::class, 'rejectLeave']);

    Route::patch('permissions/{user_id}/approve', [AdminController::class, 'approvePermission']);
    Route::patch('permissions/{user_id}/reject', [AdminController::class, 'rejectPermission']);

    Route::post('overtimes', [AdminController::class, 'storeOvertime']);
    Route::get('/attendances/all', [AdminController::class, 'getAllAttendance']);
});

// manage users
Route::get('/users', [UserController::class, 'getAllUser']);
Route::get('/users/by-role', [UserController::class, 'getUserByRole']);
Route::get('/users/raw', [UserController::class, 'getAllUserRaw']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users', [UserController::class, 'store']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::patch('/users/{id}', [UserController::class, 'update']);
});

// penggajian
Route::middleware('auth:sanctum') -> group(function () {
    Route::get('/salaries', [SalaryController::class, 'getAllSalaries']);
    Route::post('/salaries/preview', [SalaryController::class, 'getCalculatedSalaries']);
    Route::post('/salaries/save', [SalaryController::class, 'saveCalculatedSalaries']);
    Route::post('/salaries/salaries-by-month', [SalaryController::class, 'getSalariesByMonth']);

    Route::patch('/salaries/{salary}/lock', [SalaryController::class, 'toggleLock']);
    Route::patch('/salaries/lock-by-month', [SalaryController::class, 'lockByMonth']);
    // Route::patch('/salaries/{salary}/bonus', [SalaryController::class, 'updateBonus']);
    Route::patch('/salaries/{salary}/bonus', [SalaryController::class, 'updateBonus']);





});


// absensi menggunakan rfid
Route::post('/attendance/rfid/check-in', [AttendanceController::class, 'rfidCheckIn']);
Route::post('/attendance/rfid/check-out', [AttendanceController::class, 'rfidCheckOut']);
Route::get('/attendance/rfid/status', [AttendanceController::class, 'showStatusAttendance']);