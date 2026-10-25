<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'machine_ip',
        'payload_data',
    ];

    protected $casts = [
        'date' => 'date',
        'payload_data' => 'array',
    ];

    public function attendanceLogDetails()
    {
        return $this->hasMany(AttendanceLogDetail::class);
    }
}