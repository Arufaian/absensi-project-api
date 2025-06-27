<?php

namespace App\Http\Controllers;

use App\Models\LeaveQuota;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class LeaveQuotaController extends Controller
{
    use ApiResponse;

    public function index(Request $request) {

        $leaveQuota = $request -> user() -> leaveQuotas() -> first();

        return $this->success($leaveQuota, "Berhasil mendapatkan data quota cuti", 200);

    }
}