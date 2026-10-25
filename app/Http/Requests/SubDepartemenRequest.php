<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubDepartemenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 👈 Ubah jadi true
    }

    public function rules(): array
    {
        $subDeptId = $this->route('sub_department') ?? $this->route('id');

        return [
            'department_code' => 'required|exists:departments,code',
            'code'            => 'required|string|unique:sub_departments,code,' . $subDeptId,
            'name'            => 'required|string|max:255',
            'is_active'       => 'boolean',
        ];
    }
}