<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function store(Request $request)
    {
        // validate the request
        $request -> validate([
            'date' => 'required|date|after_or_equal:today',
            'reason' => 'required|string'
        ]);

        // create a new permission
        $permission = $request -> user() -> permissions() -> create([
            'date' => $request -> date,
            'reason' => $request -> reason
        ]);

        return response() -> json([
            'message' => 'Izin telah dibuat',
            'permission' => $permission
        ], 201);
    }

    public function index(Request $request)
    {
        // get all permissions for the authenticated user
        $permissions = $request -> user() -> permissions() -> get();

        return response() -> json([
            'permissions' => $permissions
        ]);
    }
}