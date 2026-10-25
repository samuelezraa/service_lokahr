<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Relasi ke SubDepartment
     */
    public function subDepartments()
    {
        // Parameter 2: foreign key di tabel sub_departments (department_code)
        // Parameter 3: local key di tabel departments (code)
        return $this->hasMany(SubDepartment::class, 'department_code', 'code');
    }
}