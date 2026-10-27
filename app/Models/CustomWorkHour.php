<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomWorkHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'start_date',
        'end_date',
        'work_scheme',
        'clock_in',
        'tolerance',
        'description',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date'   => 'date:Y-m-d',
        'is_active'  => 'boolean',
    ];
}