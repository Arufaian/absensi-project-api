<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('role', ['karyawan', 'admin', 'hrd', 'finance'])->get();
        $statuses = ['hadir', 'alpa', 'izin', 'cuti'];

        $start = Carbon::create(2025, 5, 1);
        $end = Carbon::create(2025, 5, 31);

        $workdays = collect();
        while ($start <= $end) {
            if ($start->isWeekday()) {
                $workdays->push($start->copy());
            }
            $start->addDay();
        }

        foreach ($users as $user) {
            foreach ($workdays as $day) {
                $status = collect($statuses)->random();
                // $status = 'hadir';

                // Set check-in dan check-out jika statusnya hadir atau terlambat
                $checkIn = null;
                $checkOut = null;

                if (in_array($status, ['hadir', 'terlambat'])) {
                    // Acak jam masuk antara 07:00 sampai 09:00
                    $checkIn = $day->copy()->setTime(rand(7, 9), rand(0, 59));
                    // Acak jam pulang antara 16:00 sampai 18:00
                    $checkOut = $day->copy()->setTime(rand(17, 18), rand(0, 59));
                }

                Attendance::updateOrCreate([
                    'rfid_id' => $user->rfid_id,
                    'user_id' => $user->id,
                    'date' => $day->toDateString(),
                ], [
                    'status' => $status,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                ]);
            }
        }
    }
}