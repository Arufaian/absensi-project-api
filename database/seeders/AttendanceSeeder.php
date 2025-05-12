<?php

namespace Database\Seeders;

use App\Models\Attendance;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Attendance::create([
            'user_id' => 1,
            'check_in' => now()->subHours(2),
            'check_out' => now(),
            'status' => 'Present',
        ]);
    }
}