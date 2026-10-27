<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'start_date',
        'end_date',
        'late_tolerance',
        'description',
        'is_closed',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date'   => 'date:Y-m-d',
        'is_closed'  => 'boolean',
        'is_active'  => 'boolean',
    ];
}