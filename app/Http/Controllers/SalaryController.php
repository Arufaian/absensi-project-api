<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Salary;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;


class SalaryController extends Controller
{

    use ApiResponse;

    public function getCalculatedSalaries(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = Carbon::parse($request->start_date)->startOfDay()->setTimezone('Asia/Jakarta');
        $end = Carbon::parse($request->end_date)->endOfDay()->setTimezone('Asia/Jakarta');

        if (!$start->isSameMonth($end)) {
            return $this->error("Periode gaji harus dalam bulan yang sama.", 422);
        }

        $employees = User::where('role', '!=', 'owner')->get();
        $results = [];

        foreach ($employees as $user) {
            $gaji_harian = $user->gaji_harian;
            $total_work_days = $start->diffInDaysFiltered(fn($date) => $date->isWeekday(), $end);

            $attendances = Attendance::where('user_id', $user->id)
                ->whereBetween('date', [$start, $end])
                ->get();

            $hadir = $attendances->where('status', 'hadir')->count();
            $alpa = $attendances->where('status', 'alpa')->count();
            $izin = $attendances->where('status', 'izin')->count();
            $cuti = $attendances->where('status', 'cuti')->count();
            $terlambat = $attendances->where('status', 'terlambat')->count();

            // Filter terlambat yang benar-benar perlu dipotong
            $terlambatYangDipotong = $attendances->filter(function ($attendance) {
                if ($attendance->status !== 'terlambat') return false;
                if ($attendance->check_in && $attendance->check_out) {
                    $checkIn = Carbon::parse($attendance->check_in);
                    $checkOut = Carbon::parse($attendance->check_out);
                    return $checkIn->diffInMinutes($checkOut) < (7 * 60);
                }
                return true;
            })->count();

            // Hitung potongan
            $potongan = ($alpa * $gaji_harian) +
                       ($izin * $gaji_harian * 0.2) +
                       ($terlambatYangDipotong * $gaji_harian * 0.05);

            // Hitung total menit kerja untuk sorting
            $total_work_minutes = $attendances->reduce(function ($carry, $attendance) {
                if ($attendance->check_in && $attendance->check_out) {
                    $checkIn = Carbon::parse($attendance->check_in);
                    $checkOut = Carbon::parse($attendance->check_out);
                    return $carry + $checkIn->diffInMinutes($checkOut);
                }
                return $carry;
            }, 0);

            $results[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'gaji_harian' => $gaji_harian,
                'bonus' => 0,
                'potongan' => round($potongan, 0),
                'total_salary' => round(($gaji_harian * $total_work_days) - $potongan, 0),
                'present_days' => $hadir,
                'late_days' => $terlambat,
                'absent_days' => $alpa,
                'izin_days' => $izin,
                'cuti_days' => $cuti,
                'total_work_minutes' => $total_work_minutes,
                'start_period' => $start->toDateTimeString(),
                'end_period' => $end->toDateTimeString(),
                'is_locked' => false,
                'total_work_days' => $total_work_days // Tambahan informasi
            ];
        }

        usort($results, fn($a, $b) => $b['total_work_minutes'] <=> $a['total_work_minutes']);

        return response()->json([
            'message' => 'Perhitungan gaji berhasil (preview, belum disimpan).',
            'data' => $results
        ]);
    }



    public function saveCalculatedSalaries(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = Carbon::parse($request->start_date)->startOfDay()->setTimezone('Asia/Jakarta');
        $end = Carbon::parse($request->end_date)->endOfDay()->setTimezone('Asia/Jakarta');

        if (!$start->isSameMonth($end)) {
            return $this->error("Periode gaji harus dalam bulan yang sama.", 422);
        }

        // Cek overlap dengan periode yang sudah dikunci
        $existingLockedSalaries = Salary::where(function ($query) use ($start, $end) {
            $query->whereBetween('start_period', [$start, $end])
                ->orWhereBetween('end_period', [$start, $end])
                ->orWhere(function ($query) use ($start, $end) {
                    $query->where('start_period', '<=', $start)
                          ->where('end_period', '>=', $end);
                });
        })
        ->where('is_locked', true)
        ->exists();

        if ($existingLockedSalaries) {
            return $this->error("Gagal menyimpan. Terdapat data gaji yang sudah dikunci dan overlap dengan periode yang dipilih.", 422);
        }

        $employees = User::where('role', '!=', 'owner')->get();
        $results = [];

        foreach ($employees as $user) {
            // Gunakan gaji harian langsung dari user
            $gaji_harian = $user->gaji_harian ?? 0;

            // Cek apakah data gaji sudah dikunci
            $existingSalary = Salary::where('user_id', $user->id)
                ->where('start_period', $start)
                ->where('end_period', $end)
                ->first();

            if ($existingSalary && $existingSalary->is_locked) {
                $results[] = $existingSalary;
                continue; // Skip jika sudah dikunci
            }

            // Ambil data absensi
            $attendances = Attendance::where('user_id', $user->id)
                ->whereBetween('date', [$start, $end])
                ->get();

            $hadir = $attendances->where('status', 'hadir')->count();
            $terlambat = $attendances->where('status', 'terlambat')->count();
            $alpa = $attendances->where('status', 'alpa')->count();
            $izin = $attendances->where('status', 'izin')->count();
            $cuti = $attendances->where('status', 'cuti')->count();

            // Hitung total menit kerja untuk sorting
            $total_work_minutes = $attendances->reduce(function ($carry, $attendance) {
                if ($attendance->check_in && $attendance->check_out) {
                    $checkIn = Carbon::parse($attendance->check_in);
                    $checkOut = Carbon::parse($attendance->check_out);
                    return $carry + $checkIn->diffInMinutes($checkOut);
                }
                return $carry;
            }, 0);

            // Filter keterlambatan yang benar-benar dipotong
            $terlambatYangDipotong = $attendances->filter(function ($attendance) {
                if ($attendance->status !== 'terlambat') return false;

                if ($attendance->check_in && $attendance->check_out) {
                    $checkIn = Carbon::parse($attendance->check_in);
                    $checkOut = Carbon::parse($attendance->check_out);
                    return $checkIn->diffInMinutes($checkOut) < (7 * 60);
                }
                return true;
            })->count();

            // PERUBAHAN PENTING: Hitung gaji berdasarkan hari hadir + potongan untuk keterlambatan
            $potongan = $terlambatYangDipotong * $gaji_harian * 0.05; // Hanya potong untuk keterlambatan
            $total = ($gaji_harian * $hadir) - $potongan;

            // Simpan atau perbarui data gaji
            $salary = Salary::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'start_period' => $start,
                    'end_period' => $end,
                ],
                [
                    'name' => $user->name,
                    'gaji_harian' => $gaji_harian, // Simpan gaji harian sebagai base_salary // Tambahan field untuk clarity
                    'bonus' => 0,
                    'potongan' => round($potongan, 0),
                    'total_salary' => round($total, 0),
                    'present_days' => $hadir,
                    'late_days' => $terlambat,
                    'absent_days' => $alpa,
                    'izin_days' => $izin,
                    'cuti_days' => $cuti,
                    'total_work_minutes' => $total_work_minutes,
                    'is_locked' => false
                ]
            );

            $results[] = $salary;
        }

        // Urutkan berdasarkan total menit kerja descending
        usort($results, fn($a, $b) => $b->total_work_minutes <=> $a->total_work_minutes);

        return response()->json([
            'message' => 'Gaji seluruh karyawan berhasil dihitung dan disimpan (yang belum dikunci).',
            'data' => $results
        ]);
    }


    public function getSalariesByMonth(Request $request)
    {
        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $selectedMonth = $request->input('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth()->setTimezone('Asia/Jakarta');
        $end = $start->copy()->endOfMonth();

        $salaries = Salary::with('user')
            ->whereBetween('start_period', [$start, $end])
            ->get();

        return $this->success($salaries, "Data gaji untuk bulan $selectedMonth", 200);


    }

    public function updateBonus(Request $request, Salary $salary)
    {
        $request->validate([
            'bonus' => 'required|numeric|min:0',
        ]);

        if ($salary->is_locked) {
            return $this->error("Data gaji sudah dikunci dan tidak dapat diubah.", 403);
        }

        // Validasi bonus minimal 5% dari base_salary
        $minBonus = 0.05 * $salary->base_salary;
        if ($request->bonus < $minBonus) {
            return $this->error("Bonus minimal harus 5% dari gaji pokok (Rp " . number_format($minBonus, 0, ',', '.') . ").", 422);
        }

        DB::beginTransaction();
        try {
            $salary->bonus = $request->bonus;
            $salary->total_salary = $salary->base_salary - $salary->potongan + $salary->bonus;
            $salary->save();

            DB::commit();
            return $this->success($salary, "Bonus berhasil diperbarui", 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error("Terjadi kesalahan saat memperbarui bonus.", 500);
        }
    }

    public function toggleLock(Request $request, Salary $salary)
    {
        $request->validate([
            'is_locked' => 'required|boolean',
        ]);

        $salary->is_locked = $request->is_locked;
        $salary->save();

        return response()->json([
            'message' => 'Status lock berhasil diperbarui.',
            'data' => $salary,
        ]);
    }

    public function lockByMonth(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $start = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()->setTimezone('Asia/Jakarta');
        $end = $start->copy()->endOfMonth();

        $updated = Salary::whereBetween('start_period', [$start, $end])
            ->update(['is_locked' => true]);


        return $this->success("Berhasil menunci $updated data gaji untuk bulan {$request->month}.", 200);
    }




    public function getAllSalaries(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = Salary::with('user');

        if ($request->has('start_date') && $request->has('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('start_period', [$start, $end]);
        }

        $salaries = $query->get();

        return response()->json([
            'message' => 'Berhasil mendapatkan semua data gaji.',
            'data' => $salaries
        ]);
    }





}