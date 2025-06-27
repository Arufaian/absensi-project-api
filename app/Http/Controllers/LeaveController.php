<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

use function Laravel\Prompts\error;

class LeaveController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $request -> validate([
            'start_date' =>  'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|'
        ]);

        $hasLeave = $request -> user() -> leaves() -> where('status', 'tertunda') -> exists();

        if ($hasLeave) {
            return $this->error("Cuti sebelumnya masih menunggu konfirmasi", 422);
        }

        $leave = $request -> user() -> leaves() -> create([
            'start_date' => $request -> start_date,
            'end_date' => $request -> end_date,
            'reason' => $request -> reason,
            'status' => 'tertunda'
        ]);

        return response() -> json([
            'message' => 'Pengajuan cuti berhasil ditambahkan',
            'data' => $leave
        ], 201);

    }

    public function index(Request $request)
    {
        $leaves = $request -> user() -> leaves() -> get();
        return $this->success($leaves, "Sukses mendapat data cuti", 200);
    }

    public function getAllLeave()
    {
        $leaves = Leave::latest() -> get();

        return $this->success($leaves, "Berhasil mendapatkan semua data cuti", 200);
    }
}