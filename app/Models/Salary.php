<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $fillable = [
        'user_id',
        'name',
        'start_period',
        'end_period',
        'base_salary',
        'bonus',
        'potongan',
        'total_salary',
        'present_days',
        'late_days',
        'absent_days',
        'izin_days',
        'cuti_days',
        'total_work_minutes',
        'is_locked',
        'gaji_harian'
    ];
}