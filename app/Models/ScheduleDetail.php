<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'day_of_week',
        'check_in_time',
        'check_out_time',
        'annual_event_name', // 👈 Pastikan ini annual_event_name, bukan is_day_off
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}