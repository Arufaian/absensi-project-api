<?php

namespace App\Http\Controllers;

use App\Models\User;
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

    public function rfidCheckIn(Request $request)
    {
        $request -> validate([
            'rfid_id' => 'required|string'
        ]);

        // cek apakah rfid_id sudah ada di database
        $user = User::where('rfid_id', $request -> rfid_id) -> first();

        if (!$user){
            return response() -> json([
                'message' => 'RFID ID tidak ditemukan'
            ], 404);
        }

        $today = now() -> toDateString();
        $alreadyCheckIn = $user -> attendances() -> whereDate('check_in', $today) -> first();

        // jika sudah ada data absensi pada hari ini
        if ($alreadyCheckIn){
            return response() -> json([
                'message' => 'Anda sudah melakukan check in hari ini'
            ], 400);
        }


        $attendance = $user -> attendances() -> create([
            'rfid_id' => $request -> rfid_id,
            'check_in' => now() -> toDateTimeString(),
            'status' => now()->hour <= 8 ? 'Present' : 'Late'
        ]);

        return response() -> json([
            'data' => $attendance,
            'waktu check-in' => now(),
            'message' => 'Check in berhasil'
        ]);

    }

    public function rfidCheckOut(Request $request)
    {
        // cek apakah rfid_id sudah ada di database
        $request -> validate([
            'rfid_id' => 'required|string'
        ]);

        // cek apakah user ada
        $user = User::where('rfid_id', $request -> rfid_id) -> first();

        if (!$user)
        {
            return response() -> json([
                'message' => 'Rfid_ID tidak ditemukan'
            ], 404);
        }

        $today = now() -> toDateString();
        $attendance = $user -> attendances() -> whereDate('check_in', $today) -> first();

        // jika tidak ada data absensi pada hari ini
        if (!$attendance)
        {
            return response() -> json([
                'message' => 'Anda belum melakukan check in hari ini'
            ], 400);
        }

        // jika sudah ada data absensi (check out) hari ini
        if ($attendance -> check_out)
        {
            return response() -> json([
                'message' => 'Anda sudah melakukan check out hari ini'
            ], 400);
        }

        // validasi jam pulang
        $checkOutTime = now() -> setTime(17, 0);

        if(now() -> lessThan($checkOutTime))
        {
            return response() -> json([
                'message' => 'Anda belum bisa melakukan check out, silahkan KERJAAAAAA TOLOL'
            ]);
        }

        $attendance -> update([
            'check_out' => now()
        ]);

        return response() -> json([
            'data' => $attendance,
            'message' => 'Check out berhasil'
        ]);

    }

    public function showStatusAttendance(Request $request)
    {
        $request -> validate([
            'rfid_id' => 'required|string'
        ]);


        $today = now() -> toDateString();
        $user = User::where('rfid_id', $request -> rfid_id) -> first();
        $attendance = $user -> attendances() -> whereDate('check_in', $today) -> first();

        if (!$attendance)
        {
            return response() -> json([
                'message' => 'Data absensi tidak ditemukan'
            ], 404);
        }

        return response() -> json([
            'data' => $attendance,
            'message' => 'Data absensi berhasil diambil'
        ]);
    }
}