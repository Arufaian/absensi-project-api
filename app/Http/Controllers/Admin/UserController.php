<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\LeaveQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {

        $request -> validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'gaji_harian' => 'required|numeric|min:0',
            'rfid_id' => 'required|string',
            'password' => 'required|min:8',
            'nomor_telepon' => 'required|min:12',
            'role' => 'required|in:admin,karyawan,hrd,finance',
        ]);

        $user = User::create([
            'name' => $request -> name,
            'email' => $request -> email,
            'rfid_id' => $request -> rfid_id,
            'password' => bcrypt($request -> password),
            'nomor_telepon' => $request ->nomor_telepon,
            'role' => $request -> role,
            'gaji_harian' => $request -> gaji_harian,
        ]);

        LeaveQuota::create([
            'user_id'     => $user->id,
            'year'        => Carbon::now()->year, // atau date('Y')
            'total_quota' => 12, // default jatah cuti
            'used_quota'  => 0   // awalnya 0
        ]);

        return response() -> json([
            'message' => 'User berhasil dibuat',
            'data' => $user
        ], 201);
    }

    public function destroy($id)
    {
        // Cari user berdasarkan id
        $user = User::find($id);

        // Jika tidak ditemukan, kembalikan respons error
        if (!$user) {
            return $this->error("user tidak ditemukan", 404);
        }

        $user->delete();

        return $this->success("User berhasil dihapus", 200);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error("User tidak ditemukan", 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$id}",
            'gaji_harian' => 'required|numeric|min:0',
            'rfid_id' => 'required|string',
            'password' => 'nullable|min:8',
            'nomor_telepon' => 'required|min:12',
            'role' => 'required|in:admin,karyawan,hrd,finance',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'rfid_id' => $request->rfid_id,
            'password' => $request->password ? bcrypt($request->password) : $user->password,
            'nomor_telepon' => $request->nomor_telepon,
            'role' => $request->role,
            'gaji_harian' => $request->gaji_harian,
        ]);

        return $this->success("User berhasil diupdate", 200);
    }




    public function getAllUser(Request $request) {
        // $currentUserId = Auth::id(); // ID user yang sedang login
        $users = User::where('role', '!=', 'owner')->paginate(10);
        return $this->success($users, "Berhasil mendapatkan semua user", 200);
    }

    public function getAllUserRaw(Request $request) {
        // $currentUserId = Auth::id(); // ID user yang sedang login
        $users = User::where('role', '!=', 'owner')->get();


        return $this->success($users, "Berhasil mendapatkan semua user", 200);
    }

    public function getUserByRole()
    {
        $usersByRole = User::selectRaw('role, count(*) as count')
                        ->groupBy('role')
                        ->get()
                        ->pluck('count', 'role');

        return $this->success($usersByRole, "Berhasil mendapatkan jumlah user per role", 200);
    }



}