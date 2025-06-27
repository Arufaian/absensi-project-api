<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    use ApiResponse;

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

        return $this->success($permission, "Izin berhasil dibuat", 201);
    }

    public function index(Request $request)
    {
        $permissions = $request -> user() -> permissions() -> get();

        return $this->success($permissions, "Data izin berhasil diambil", 200);

    }
}