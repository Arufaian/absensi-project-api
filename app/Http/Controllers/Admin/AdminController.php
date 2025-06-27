<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Permission;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\LeaveQuota;


class AdminController extends Controller
{
    use ApiResponse;



    public function approveLeave(Request $request, $user_id)
    {
        $leave = Leave::where('user_id', $user_id)->latest()->firstOrFail();

        DB::beginTransaction();

        try {
            // Hitung jumlah hari cuti
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);
            $days = $start->diffInDaysFiltered(function (Carbon $date) {
                return !$date->isWeekend(); // hanya hari kerja
            }, $end->copy()->addDay()); // tambah 1 hari karena end_date termasuk

            // Ambil kuota cuti untuk tahun ini
            $quota = LeaveQuota::where('user_id', $user_id)
                        ->where('year', $start->year)
                        ->firstOrFail();

            // Validasi sisa kuota cukup
            if (($quota->total_quota - $quota->used_quota) < $days) {
                return $this->error("Kuota cuti tidak mencukupi", 422);
            }

            // Update status cuti
            $leave->update(['status' => 'disetujui']);

            // Update kuota cuti
            $quota->increment('used_quota', $days);

            $quota -> decrement('total_quota', $days);

            // Tambahkan absensi per hari cuti
            $date = $start->copy();
            while ($date->lte($end)) {
                if (!$date->isWeekend()) {
                    Attendance::create([
                        'user_id' => $user_id,
                        'date' => $date->toDateString(),
                        'check_in' => null,
                        'check_out' => null,
                        'status' => 'cuti',
                    ]);
                }
                $date->addDay();
            }

            DB::commit();
            return $this->success($leave, "Cuti berhasil disetujui dan kuota diperbarui", 200);

        } catch (\Exception $e) {
            DB::rollback();
            return $this->error("Gagal menyetujui cuti: " . $e->getMessage(), 500);
        }
    }

    public function rejectLeave($user_id)
    {
        DB::beginTransaction();

        try {
            // Ambil pengajuan cuti terakhir yang masih tertunda
            $leave = Leave::where('user_id', $user_id)
                ->where('status', 'tertunda')
                ->latest()
                ->firstOrFail();

            // Update status menjadi ditolak
            $leave->update(['status' => 'ditolak']);

            DB::commit();

            // return response()->json([
            //     'message' => 'Pengajuan cuti berhasil ditolak',
            //     'data' => $leave
            // ], 200);

            return $this->success($leave, "pengajuan cuti berhasil ditolak", 200);

        } catch (\Exception $e) {
            DB::rollBack();

            // return response()->json([
            //     'message' => 'Gagal menolak pengajuan cuti: ' . $e->getMessage()
            // ], 500);

            return $this->error("Gagal menolak pengajuan cuti: " . $e->getMessage(), 500);
        }
    }


    public function getAllLeavesBasedOnRole(Request $request)
    {
        $user = $request->user();
        $role = $user->role;

        if ($role === 'owner') {
            // Owner bisa akses semuanya
            $leaves = Leave::all();
        } elseif (in_array($role, ['admin', 'hrd'])) {
            // Admin atau HR akses hanya yang bukan dirinya dan bukan owner
            $leaves = Leave::whereHas('user', function ($query) use ($user) {
                $query->where('id', '!=', $user->id)
                      ->where('role', '!=', 'owner');
            })->get();
        } else {
            // Role lain, misalnya user biasa, error
            return $this->error("Akses ditolak", 403);
        }

        // return response([
        //     'permissions' => $permissions
        // ]);

        return $this->success($leaves, "Data cuti berhasil diambil", 200);
    }

    public function approvePermission($user_id)
    {
        DB::beginTransaction();

        try {
            // Ambil data izin terbaru milik user
            $permission = Permission::where('user_id', $user_id)
            ->where('status', 'tertunda')
            ->latest()
            ->firstOrFail();

            // Parse tanggal izin
            $date = Carbon::parse($permission->date);

            // Jika hari kerja (tidak akhir pekan), buat data absensi
            if (!$date->isWeekend()) {
                $permission->update(['status' => 'disetujui']);

                Attendance::create([
                    'user_id' => $user_id,
                    'date' => $date->toDateString(),
                    'check_in' => null,
                    'check_out' => null,
                    'status' => 'izin',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Izin berhasil disetujui dan absensi ditambahkan'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyetujui izin: ' . $e->getMessage()
            ], 500);
        }
    }


    public function rejectPermission($user_id)
    {
        try {
            $permission = Permission::where('user_id', $user_id)
                ->where('status', 'tertunda')
                ->latest()
                ->firstOrFail();

            $permission->update(['status' => 'ditolak']);

            return response()->json([
                'message' => 'Izin berhasil ditolak',
                'data' => $permission
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menolak izin: ' . $e->getMessage()
            ], 500);
        }
    }




    public function getAllPermissionsBasedOnRole(Request $request)
    {
        $user = $request->user();
        $role = $user->role;

        if ($role === 'owner') {
            // Owner bisa akses semuanya
            $permissions = Permission::all();
        } elseif (in_array($role, ['admin', 'hrd'])) {
            // Admin atau HR akses hanya yang bukan dirinya dan bukan owner
            $permissions = Permission::whereHas('user', function ($query) use ($user) {
                $query->where('id', '!=', $user->id)
                      ->where('role', '!=', 'owner');
            })->get();
        } else {
            // Role lain, misalnya user biasa, error
            return $this->error("Akses ditolak", 403);
        }

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

    // public function allAttendances()
    // {
    //     $data = Attendance::with('user')->latest()->get();

    //     return response()->json(['data' => $data]);
    // }





    public function getAllAttendance(Request $request)
    {

        $perPage = $request->get('per_page', 10); // default 10 per halaman

        $attendances = Attendance::with(['user:id,name']) // <-- Tambahkan di sini
            ->whereHas('user', function ($query) {
                $query->where('role', '!=', 'owner');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $attendances->items(),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }





}