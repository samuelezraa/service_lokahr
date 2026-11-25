<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'nip',
        'employee_password',
        'full_name',
        'email',
        'phone',
        'department_id',
        'sub_department_id',
        'position_id',
        'grade_id',
        'work_schedule_scheme_id',
        'join_date',
        'birth_date',
        'age_years',
        'work_duration_months',
        'is_active',
    ];

    protected $hidden = [
        'employee_password',
    ];

    protected $casts = [
        'join_date'            => 'date:d-m-Y',
        'birth_date'           => 'date:d-m-Y',
        'age_years'            => 'integer',
        'work_duration_months' => 'integer',
        'is_active'            => 'boolean',
    ];

    /**
     * Konfigurasi Spatie Activitylog untuk mencatat perubahan Before vs After
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Master Data')
            ->logFillable()
            ->logExcept(['employee_password'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Function phoneconvert:
     * Jika input awal "0", diganti menjadi "62".
     * Maksimal panjang karakter adalah 14.
     */
    public static function phoneconvert(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Bersihkan selain angka
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if ($phone === '') {
            return null;
        }

        // Jika sudah diawali "62", biarkan apa adanya
        if (str_starts_with($phone, '62')) {
            // sudah format 62...
        } elseif (str_starts_with($phone, '0')) {
            // Jika diawali "0", ganti "0" di depan dengan "62"
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            // Jika diawali "8" (tanpa 0), tambahkan "62" di depan tanpa membuang "8"
            $phone = '62' . $phone;
        }

        // Batasi maksimal 14 karakter
        if (strlen($phone) > 14) {
            $phone = substr($phone, 0, 14);
        }

        return $phone;
    }

    // Accessor untuk mengubah format tanggal saat diambil API (DD-MM-YYYY)
    public function getJoinDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d-m-Y') : null;
    }

    public function getBirthDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d-m-Y') : null;
    }

    // Relasi
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function subDepartment()
    {
        return $this->belongsTo(SubDepartment::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function workScheduleScheme()
    {
        return $this->belongsTo(WorkScheduleScheme::class);
    }

    /**
     * Relasi ke AttendanceLogDetail berdasarkan NIP atau Employee ID
     */
    public function attendanceLogDetails()
    {
        return $this->hasMany(AttendanceLogDetail::class, 'nip', 'nip');
    }

    /**
     * Relasi ke EmployeeAttendance (Rekapitulasi Kehadiran per Periode)
     */
    public function attendances()
    {
        return $this->hasMany(EmployeeAttendance::class, 'nip', 'nip');
    }
}