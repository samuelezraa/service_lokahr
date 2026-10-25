<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomWorkHour extends Model
{
    use HasFactory;

   protected $fillable = [
    'name',
    'clock_in',
    'clock_out',
    'tolerance', // 👈 Tambahkan ini
    'description',
    'is_active',
];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}