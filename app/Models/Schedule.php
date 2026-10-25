<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scheduleDetails()
    {
        return $this->hasMany(ScheduleDetail::class);
    }

    public function scheduleSchemes()
    {
        return $this->hasMany(ScheduleScheme::class);
    }
}