<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function store(Request $request)
    {
        $request -> validate([
            'start_date' =>  'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|'
        ]);




        $leave = $request -> user() -> leaves() -> create([
            'start_date' => $request -> start_date,
            'end_date' => $request -> end_date,
            'reason' => $request -> reason,
            'status' => 'Pending'
        ]);

        return response() -> json([
            'message' => 'Pengajuan cuti berhasil ditambahkan',
            'data' => $leave
        ], 201);

    }
}