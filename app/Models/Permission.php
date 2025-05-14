<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'date',
        'reason'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}