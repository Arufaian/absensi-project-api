<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // fetch data absensi
        $attendances = $request -> user() -> attendances() -> get();

        return response() -> json([
            'data' => $attendances,
            'message' => 'Data absensi berhasil diambil'
        ]);

    }


    public function statusToday(Request $request)
    {

        $today = now() -> toDateString();

        // fetch data absensi pada hari ini
        $attendance = $request -> user() -> attendances()
        -> whereDate('check_in', $today)
        -> orWhereDate('check_out', $today)
        -> first();

        return response() -> json([
            'data' => $attendance,
            'message' => 'Data absensi hari ini berhasil diambil'
        ]);
    }
}