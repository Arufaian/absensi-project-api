<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Permission;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function approveLeave(Request $request, $user_id)
    {
        // $leave = Leave::findOrFail($id);
        $leave = Leave::where('user_id', $user_id)->latest()->firstOrFail();
        $leave -> update(['status' => 'approved']);

        if ($request->user()->id === $leave->user_id && $request->user()->role === 'admin') {
            return response()->json(['message' => 'Admin tidak boleh menyetujui cuti miliknya sendiri.'], 403);
        }

        if ($request -> user() -> role === 'karyawan') {
            return response()->json(['message' => 'yang bener aja lu.'], 403);
        }

        return response([
            'message' => 'Leave approved successfullyy'
        ]);
    }

    public function getAllLeave()
    {
        $leaves = Leave::all();

        return response([
            'leaves' => $leaves
        ]);
    }

    public function approvePermission($user_id)
    {
        // $permission = Permission::findOrFail($user_id);


        $permission = Permission::where('user_id', $user_id)->latest()->firstOrFail();

        $permission -> update(['status' => 'Approved']);

        return response([
            'message' => 'permission approved successfullyy'
        ]);
    }

    public function getAllPermission()
    {
        $permissions = Permission::all();

        return response([
            'permissions' => $permissions
        ]);
    }


    public function storeOvertime(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'duration' => 'required|numeric|min:1',
            'reason' => 'nullable|string',
        ]);

        $overtime = Overtime::create($request->all());

        return response()->json(['message' => 'Overtime added', 'data' => $overtime], 201);
    }

    public function allAttendances()
    {
        $data = Attendance::with('user')->latest()->get();

        return response()->json(['data' => $data]);
    }



}