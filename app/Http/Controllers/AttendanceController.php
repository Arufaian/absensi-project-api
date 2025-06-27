<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;


class AttendanceController extends Controller
{

    use ApiResponse;

    private function ensureLeaveQuotaForCurrentYear(User $user)
    {
        $currentYear = now()->year;
        $hasQuota = $user->leaveQuotas()->where('year', $currentYear)->exists();

        if (!$hasQuota) {
            $user->leaveQuota()->create([
                'year' => $currentYear,
                'total_quota' => 12, // Sesuaikan default sistemmu
                'used_quota' => 0,
            ]);
        }
    }




    // public function index(Request $request)
    // {
    //     // fetch data absensi
    //     $attendances = $request -> user() -> attendances() -> get();
    //     return $this->success($attendances, "Data absensi berhasil diambil", 200);

    // }

    public function index(Request $request)
    {
        $query = $request->user()->attendances() -> latest();

        $attendances = $query->paginate($request->per_page ?? 10);

        return $this->success($attendances, "Data absensi berhasil diambil", 200);
    }

    public function getAllAttendance()
    {
        $attendances = Attendance::whereHas('user', function ($query) {
            $query->where('role', '!=', 'owner');
        })->get();

        return $this->success($attendances, "Data absensi berhasil diambil (kecuali owner)", 200);
    }



    public function getDailyAttendance()
    {
        $today = now()->format('Y-m-d'); // Mendapatkan tanggal hari ini dalam format Y-m-d

        $attendances = Attendance::whereDate('date', $today)
            ->whereHas('user', function ($query) {
                $query->where('role', '!=', 'owner');
            })->get();

            $statusCounts = [
                'hadir' => $attendances->where('status', 'hadir')->count(),
                'izin' => $attendances->where('status', 'izin')->count(),
                'sakit' => $attendances->where('status', 'sakit')->count(),
                'alfa' => $attendances->where('status', 'alfa')->count(),
            ];


        return $this->success($statusCounts, "Data statistik absensi berhasil diambil", 200);
    }

    public function monthlyAttendance(Request $request)
    {
        $user = $request -> user();

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $attendances = $user->attendances()
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
        ->get();

        return $this->success($attendances, "Data absensi bulanan berhasil diambil", 200);
    }

    // AttendanceController.php
    public function getMonthlyAttendance()
    {
        $monthlyData = Attendance::whereHas('user', fn($q) => $q->where('role', '!=', 'owner'))
            ->selectRaw('MONTH(date) as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month');

        // Format data untuk semua bulan (1-12)
        $formattedData = [];
        for ($i = 1; $i <= 12; $i++) {
            $formattedData[$i] = $monthlyData->get($i, 0);
        }

        return $this->success($formattedData, "Statistik absensi bulanan", 200);
    }


    public function statusToday(Request $request)
    {

        $today = now() -> toDateString();
        $attendance = $request -> user() -> attendances()
        -> whereDate('check_in', $today)
        -> orWhereDate('check_out', $today)
        -> first();

        return $this-> success($attendance, "Data absensi hari ini berhasil diambil");
    }

    public function rfidCheckIn(Request $request)
    {
        $request->validate([
            'rfid_id' => 'required|string'
        ]);

        // Cari user berdasarkan RFID
        $user = User::where('rfid_id', $request->rfid_id)->first();
        if (!$user) {
            return $this->error('RFID tidak ditemukan', 404);
        }

        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i');

        // Batas waktu check-in (06:00 - 09:00)
        $startTime = $now->copy()->setTime(6, 0);
        $endTime = $now->copy()->setTime(9, 0);
        // if (!$now->between($startTime, $endTime)) {
        //     return $this->error('Check-in hanya diperbolehkan antara 06:00 - 09:00', 403);
        // }

        // Cek apakah sudah check-in hari ini
        $alreadyCheckIn = $user->attendances()->whereDate('check_in', $today)->first();
        if ($alreadyCheckIn) {
            return $this->error('Anda sudah melakukan check-in hari ini', 400);
        }

        // Auto-reset kuota cuti jika tahun berganti
        $this->ensureLeaveQuotaForCurrentYear($user);

        // Buat data absensi
        $attendance = $user->attendances()->create([
            'rfid_id' => $request->rfid_id,
            'date' => $today,
            'check_in' => $now->toDateTimeString(),
            'status' => $currentTime <= '08:00' ? 'hadir' : 'terlambat'
        ]);

        return $this->success($attendance, 'Check-in berhasil', 200);
    }


    public function rfidCheckOut(Request $request)
    {
        $request->validate([
            'rfid_id' => 'required|string'
        ]);

        $user = User::where('rfid_id', $request->rfid_id)->first();
        if (!$user) {
            return $this->error('RFID tidak ditemukan', 404);
        }

        $now = now();
        $today = $now->toDateString();

        $attendance = $user->attendances()->whereDate('check_in', $today)->first();
        if (!$attendance) {
            return $this->error('Anda belum melakukan check-in hari ini', 400);
        }

        if ($attendance->check_out) {
            return $this->error('Anda sudah melakukan check-out hari ini', 409);
        }

        // Batas check-out: 17:00 - 20:00
        $startCheckOut = $now->copy()->setTime(17, 0);
        $endCheckOut = $now->copy()->setTime(20, 0);

        if (!$now->between($startCheckOut, $endCheckOut)) {
            return $this->error('Check-out hanya diperbolehkan antara jam 17:00 - 20:00', 403);
        }

        // Simpan waktu check-out
        $attendance->update([
            'check_out' => $now
        ]);

        return $this->success($attendance, 'Check-out berhasil', 200);
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
            return $this->error('Anda belum melakukan absensi pada hari ini', 400);
        }

        return $this->success($attendance, 'Data absensi berhasil diambil', 200);
    }


    public function usersNotCheckedInToday()
    {
        $today = now()->toDateString();

        $excludedRoles = ['owner'];

        $users = User::whereNotIn('role', $excludedRoles)
            ->whereDoesntHave('attendances', function ($query) use ($today) {
                $query->whereDate('date', $today);
            })
            ->get();

        return $this->success($users, 'Daftar user yang belum check in hari ini (kecuali owner)', 200);
    }

    public function markAbsentAsAlpa()
    {
        $now = now();
        $today = $now->toDateString();

        // Validasi: hanya bisa eksekusi setelah jam 09:00
        $cutoffTime = $now->copy()->setTime(9, 0);
        if ($now->lessThan($cutoffTime)) {
            return $this->error('Belum bisa menandai karyawan sebagai alpa sebelum jam 09:00', 403);
        }

        $excludedRoles = ['owner'];

        $users = User::whereNotIn('role', $excludedRoles)
            ->whereDoesntHave('attendances', function ($query) use ($today) {
                $query->whereDate('date', $today);
            })
            ->get();

        foreach ($users as $user) {
            $user->attendances()->create([
                'rfid_id' => null,
                'check_in' => null,
                'check_out' => null,
                'date' => $today,
                'status' => 'alpa'
            ]);
        }

        return $this->success(null, count($users) . ' user ditandai sebagai alpa', 200);
    }

}