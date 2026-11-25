<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Position;
use App\Models\Grade;
use App\Models\WorkScheduleScheme;
use App\Jobs\CalculateEmployeeMetricsJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeeImport implements ToArray, WithHeadingRow
{
    private function parseDate($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d');
            }

            $cleanDate = str_replace('/', '-', trim((string) $value));

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $cleanDate)) {
                return $cleanDate;
            }

            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $cleanDate)) {
                return Carbon::createFromFormat('d-m-Y', $cleanDate)->format('Y-m-d');
            }

            return Carbon::parse($cleanDate)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function resolveMasterId(string $modelClass, string $column, mixed $rawValue): ?int
    {
        $val = trim((string) ($rawValue ?? ''));
        if ($val === '') {
            return null;
        }

        if (is_numeric($val)) {
            $id = (int) $val;
            return $modelClass::where('id', $id)->exists() ? $id : null;
        }

        $lowerVal = mb_strtolower($val);
        $table = (new $modelClass)->getTable();
        $query = $modelClass::query()->whereRaw("LOWER(TRIM({$column})) = ?", [$lowerVal]);

        if ($column !== 'code' && Schema::hasColumn($table, 'code')) {
            $query->orWhereRaw("LOWER(TRIM(code)) = ?", [$lowerVal]);
        }

        $foundId = $query->value('id');
        return $foundId ? (int) $foundId : null;
    }

    private function validateSubDepartmentBelongsToDepartment(int $subDepartmentId, int $departmentId, int $rowNumber, string $rawSubDept, string $rawDept): void
    {
        $subDept = SubDepartment::find($subDepartmentId);
        if (!$subDept) {
            throw new \Exception("Baris {$rowNumber} - Kolom Sub Departemen ('{$rawSubDept}'): tidak ditemukan di data master Sub Departemen.");
        }

        $table = $subDept->getTable();
        $parentDeptId = null;

        if (Schema::hasColumn($table, 'department_id') && !is_null($subDept->department_id)) {
            $parentDeptId = (int) $subDept->department_id;
        } elseif (Schema::hasColumn($table, 'departemen_id') && !is_null($subDept->departemen_id)) {
            $parentDeptId = (int) $subDept->departemen_id;
        }

        if (!is_null($parentDeptId) && $parentDeptId !== $departmentId) {
            throw new \Exception("Baris {$rowNumber} - Kolom Sub Departemen ('{$rawSubDept}'): tidak sesuai dengan Departemen ('{$rawDept}').");
        }
    }

    private function validatePositionBelongsToHierarchy(int $positionId, int $subDepartmentId, int $departmentId, int $rowNumber, string $rawPos, string $rawSubDept, string $rawDept): void
    {
        $pos = Position::find($positionId);
        if (!$pos) {
            throw new \Exception("Baris {$rowNumber} - Kolom POS ('{$rawPos}'): tidak ditemukan di data master POS.");
        }

        $table = $pos->getTable();
        $parentSubDeptId = null;
        $parentDeptId = null;

        if (Schema::hasColumn($table, 'sub_department_id') && !is_null($pos->sub_department_id)) {
            $parentSubDeptId = (int) $pos->sub_department_id;
        } elseif (Schema::hasColumn($table, 'sub_departemen_id') && !is_null($pos->sub_departemen_id)) {
            $parentSubDeptId = (int) $pos->sub_departemen_id;
        }

        if (Schema::hasColumn($table, 'department_id') && !is_null($pos->department_id)) {
            $parentDeptId = (int) $pos->department_id;
        } elseif (Schema::hasColumn($table, 'departemen_id') && !is_null($pos->departemen_id)) {
            $parentDeptId = (int) $pos->departemen_id;
        }

        if (!is_null($parentSubDeptId) && $parentSubDeptId !== $subDepartmentId) {
            throw new \Exception("Baris {$rowNumber} - Kolom POS ('{$rawPos}'): tidak sesuai dengan Sub Departemen ('{$rawSubDept}').");
        }

        if (!is_null($parentDeptId) && $parentDeptId !== $departmentId) {
            throw new \Exception("Baris {$rowNumber} - Kolom POS ('{$rawPos}'): tidak sesuai dengan Departemen ('{$rawDept}').");
        }
    }

    public function array(array $array): void
    {
        DB::transaction(function () use ($array) {
            $seenEmployeeIds = [];
            $seenNips = [];
            $seenPhones = [];
            $hasImportedRow = false;

            foreach ($array as $index => $row) {
                $rowNumber = $index + 2; // +2 karena baris 1 adalah header/heading

                $rawEmpId     = trim((string) ($row['id_karyawan'] ?? $row['employee_id'] ?? ''));
                $rawNip       = trim((string) ($row['nip'] ?? ''));
                $nama         = trim((string) ($row['nama'] ?? $row['nama_lengkap'] ?? $row['full_name'] ?? ''));
                $email        = trim((string) ($row['email'] ?? ''));
                $rawPhone     = trim((string) ($row['no_telepon'] ?? $row['telepon'] ?? $row['phone'] ?? ''));
                $rawDept      = trim((string) ($row['departemen'] ?? $row['department'] ?? ''));
                $rawSubDept   = trim((string) ($row['sub_departemen'] ?? $row['sub_department'] ?? ''));
                $rawPos       = trim((string) ($row['pos'] ?? $row['position'] ?? ''));
                $rawGrade     = trim((string) ($row['grade'] ?? ''));
                $rawScheme    = trim((string) ($row['skema_hari_kerja'] ?? $row['work_schedule_scheme'] ?? ''));
                $rawJoinDate  = $row['tanggal_bergabung'] ?? $row['join_date'] ?? null;
                $rawBirthDate = $row['tanggal_lahir'] ?? $row['birth_date'] ?? null;

                // Lewati baris yang seluruhnya kosong
                $isAllEmpty =
                    $rawEmpId === '' &&
                    $rawNip === '' &&
                    $nama === '' &&
                    $email === '' &&
                    $rawPhone === '' &&
                    $rawDept === '' &&
                    $rawSubDept === '' &&
                    $rawPos === '' &&
                    $rawGrade === '' &&
                    $rawScheme === '' &&
                    ($rawJoinDate === null || trim((string) $rawJoinDate) === '') &&
                    ($rawBirthDate === null || trim((string) $rawBirthDate) === '');

                if ($isAllEmpty) {
                    continue;
                }

                // 1. Kolom ID Karyawan (wajib, 4 digit angka, unik)
                if ($rawEmpId === '') {
                    throw new \Exception("Baris {$rowNumber} - Kolom ID Karyawan: wajib diisi.");
                }
                if (!preg_match('/^\d{4}$/', $rawEmpId)) {
                    throw new \Exception("Baris {$rowNumber} - Kolom ID Karyawan ('{$rawEmpId}'): harus berupa 4 digit angka.");
                }
                if (in_array($rawEmpId, $seenEmployeeIds, true)) {
                    throw new \Exception("Baris {$rowNumber} - Kolom ID Karyawan ('{$rawEmpId}'): duplikat dengan baris lain di file import.");
                }
                if (Employee::where('employee_id', $rawEmpId)->exists()) {
                    throw new \Exception("Baris {$rowNumber} - Kolom ID Karyawan ('{$rawEmpId}'): sudah terdaftar di database.");
                }

                // 2. Kolom NIP (wajib, 9 digit angka, unik)
                if ($rawNip === '') {
                    throw new \Exception("Baris {$rowNumber} - Kolom NIP: wajib diisi.");
                }
                if (!preg_match('/^\d{9}$/', $rawNip)) {
                    throw new \Exception("Baris {$rowNumber} - Kolom NIP ('{$rawNip}'): harus berupa 9 digit angka.");
                }
                if (in_array($rawNip, $seenNips, true)) {
                    throw new \Exception("Baris {$rowNumber} - Kolom NIP ('{$rawNip}'): duplikat dengan baris lain di file import.");
                }
                if (Employee::where('nip', $rawNip)->exists()) {
                    throw new \Exception("Baris {$rowNumber} - Kolom NIP ('{$rawNip}'): sudah terdaftar di database.");
                }

                // 3. Kolom Nama Lengkap (wajib)
                if ($nama === '') {
                    throw new \Exception("Baris {$rowNumber} - Kolom Nama Lengkap: wajib diisi.");
                }

                // 4. Kolom Email (opsional, tapi jika diisi harus format email valid)
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception("Baris {$rowNumber} - Kolom Email ('{$email}'): format email tidak valid.");
                }

                // 5. Kolom No. Telepon (wajib, terkonversi, unik)
                if ($rawPhone === '') {
                    throw new \Exception("Baris {$rowNumber} - Kolom No. Telepon: wajib diisi.");
                }
                $phone = Employee::phoneconvert($rawPhone);
                if (empty($phone)) {
                    throw new \Exception("Baris {$rowNumber} - Kolom No. Telepon ('{$rawPhone}'): format nomor telepon tidak valid.");
                }
                if (in_array($phone, $seenPhones, true)) {
                    throw new \Exception("Baris {$rowNumber} - Kolom No. Telepon ('{$rawPhone}'): duplikat dengan baris lain di file import.");
                }
                if (Employee::where('phone', $phone)->exists()) {
                    throw new \Exception("Baris {$rowNumber} - Kolom No. Telepon ('{$rawPhone}'): sudah terdaftar di database.");
                }

                // 6. Kolom Departemen (wajib & harus ada di master)
                if ($rawDept === '') {
                    throw new \Exception("Baris {$rowNumber} - Kolom Departemen: wajib diisi.");
                }
                $departmentId = $this->resolveMasterId(Department::class, 'name', $rawDept);
                if (!$departmentId) {
                    throw new \Exception("Baris {$rowNumber} - Kolom Departemen ('{$rawDept}'): tidak ditemukan di data master Departemen.");
                }

                // 7. Kolom Sub Departemen (wajib, ada di master, & sesuai Departemen)
                if ($rawSubDept === '') {
                    throw new \Exception("Baris {$rowNumber} - Kolom Sub Departemen: wajib diisi.");
                }
                $subDepartmentId = $this->resolveMasterId(SubDepartment::class, 'name', $rawSubDept);
                if (!$subDepartmentId) {
                    throw new \Exception("Baris {$rowNumber} - Kolom Sub Departemen ('{$rawSubDept}'): tidak ditemukan di data master Sub Departemen.");
                }
                $this->validateSubDepartmentBelongsToDepartment($subDepartmentId, $departmentId, $rowNumber, $rawSubDept, $rawDept);

                // 8. Kolom POS (wajib, ada di master, & sesuai Sub Departemen / Departemen)
                if ($rawPos === '') {
                    throw new \Exception("Baris {$rowNumber} - Kolom POS: wajib diisi.");
                }
                $positionId = $this->resolveMasterId(Position::class, 'title', $rawPos);
                if (!$positionId) {
                    throw new \Exception("Baris {$rowNumber} - Kolom POS ('{$rawPos}'): tidak ditemukan di data master POS.");
                }
                $this->validatePositionBelongsToHierarchy($positionId, $subDepartmentId, $departmentId, $rowNumber, $rawPos, $rawSubDept, $rawDept);

                // 9. Kolom Grade (wajib & harus ada di master)
                if ($rawGrade === '') {
                    throw new \Exception("Baris {$rowNumber} - Kolom Grade: wajib diisi.");
                }
                $gradeId = $this->resolveMasterId(Grade::class, 'name', $rawGrade);
                if (!$gradeId) {
                    throw new \Exception("Baris {$rowNumber} - Kolom Grade ('{$rawGrade}'): tidak ditemukan di data master Grade.");
                }

                // 10. Kolom Skema Hari Kerja (wajib & harus ada di master)
                if ($rawScheme === '') {
                    throw new \Exception("Baris {$rowNumber} - Kolom Skema Hari Kerja: wajib diisi.");
                }
                $schemeId = $this->resolveMasterId(WorkScheduleScheme::class, 'name', $rawScheme);
                if (!$schemeId) {
                    throw new \Exception("Baris {$rowNumber} - Kolom Skema Hari Kerja ('{$rawScheme}'): tidak ditemukan di data master Skema Hari Kerja.");
                }

                // 11. Kolom Tanggal Bergabung (wajib & tanggal valid)
                if ($rawJoinDate === null || trim((string) $rawJoinDate) === '') {
                    throw new \Exception("Baris {$rowNumber} - Kolom Tanggal Bergabung: wajib diisi.");
                }
                $joinDate = $this->parseDate($rawJoinDate);
                if (!$joinDate) {
                    throw new \Exception("Baris {$rowNumber} - Kolom Tanggal Bergabung ('" . trim((string) $rawJoinDate) . "'): format tanggal tidak valid.");
                }

                // 12. Kolom Tanggal Lahir (wajib & tanggal valid)
                if ($rawBirthDate === null || trim((string) $rawBirthDate) === '') {
                    throw new \Exception("Baris {$rowNumber} - Kolom Tanggal Lahir: wajib diisi.");
                }
                $birthDate = $this->parseDate($rawBirthDate);
                if (!$birthDate) {
                    throw new \Exception("Baris {$rowNumber} - Kolom Tanggal Lahir ('" . trim((string) $rawBirthDate) . "'): format tanggal tidak valid.");
                }

                // Catat nilai unik yang sudah lolos validasi pada file ini
                $seenEmployeeIds[] = $rawEmpId;
                $seenNips[]        = $rawNip;
                $seenPhones[]      = $phone;
                $hasImportedRow    = true;

                // Hitung langsung Umur & Lama Kerja (dibulatkan ke bawah tanpa koma)
                $now                = Carbon::now();
                $ageYears           = (int) floor(Carbon::parse($birthDate)->diffInYears($now));
                $workDurationMonths = (int) floor(Carbon::parse($joinDate)->diffInMonths($now));

                Employee::create([
                    'employee_id'             => $rawEmpId,
                    'nip'                     => $rawNip,
                    'employee_password'       => Hash::make($rawNip),
                    'full_name'               => $nama,
                    'email'                   => $email !== '' ? $email : null,
                    'phone'                   => $phone,
                    'department_id'           => $departmentId,
                    'sub_department_id'       => $subDepartmentId,
                    'position_id'             => $positionId,
                    'grade_id'                => $gradeId,
                    'work_schedule_scheme_id' => $schemeId,
                    'join_date'               => $joinDate,
                    'birth_date'              => $birthDate,
                    'age_years'               => $ageYears,
                    'work_duration_months'    => $workDurationMonths,
                    'is_active'               => isset($row['status']) && trim((string) $row['status']) !== ''
                        ? (in_array(mb_strtolower(trim((string) $row['status'])), ['aktif', 'active', '1', 'true'], true))
                        : true,
                ]);
            }

            if (!$hasImportedRow) {
                throw new \Exception("File import tidak memiliki data karyawan yang valid untuk diproses.");
            }
        });

        CalculateEmployeeMetricsJob::dispatch();
    }
}