<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubDepartment extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    /**
     * Relasi ke Department Induk
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_code', 'code');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Master Data')
            ->logUnguarded()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}