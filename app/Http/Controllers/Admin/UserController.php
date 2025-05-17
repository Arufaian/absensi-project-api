<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Request $request)
    {

        if (!$request->user()->isAdmin() && !$request->user()->isOwner()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request -> validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'rfid_id' => 'required|string',
            'password' => 'required|min:8',
            'role' => 'required|in:admin,karyawan'
        ]);


        $user = User::create([
            'name' => $request -> name,
            'email' => $request -> email,
            'rfid_id' => $request -> rfid_id,
            'password' => bcrypt($request -> password),
            'role' => $request -> role
        ]);

        return response() -> json([
            'message' => 'User berhasil dibuat',
            'data' => $user
        ], 201);
    }
}