<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        return $this->attemptLogin(
            $request->input('username'),
            $request->input('password')
        );
    }

    public function authByUrl(Request $request)
    {
        $idKaryawan   = trim((string) $request->input('idkaryawan', ''));
        $password     = (string) $request->input('password', '');
        $idDepartemen = trim((string) $request->input('id_departemen', ''));
        $departemen   = trim((string) $request->input('departemen', ''));

        if ($idKaryawan === '' || $password === '') {
            return response()->json([
                'success' => false,
                'message' => 'Parameter idkaryawan dan password wajib diisi!',
            ], 422);
        }

        return $this->attemptLogin($idKaryawan, $password, $idDepartemen, $departemen);
    }

    private function attemptLogin(
        string $identifier,
        string $password,
        string $idDepartemen = '',
        string $departemen = ''
    ) {
        $user = User::with('department')
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password yang Anda masukkan salah!',
            ], 401);
        }

        // Validasi tambahan jika id_departemen atau departemen ikut dikirim di URL
        if ($idDepartemen !== '' && (string) $user->department_id !== $idDepartemen) {
            $deptByCode = Department::where('code', $idDepartemen)->first();
            if (! $deptByCode || (string) $user->department_id !== (string) $deptByCode->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Departemen tidak sesuai dengan akun karyawan!',
                ], 403);
            }
        }

        $deptRelation = $user->getRelationValue('department');
        $deptRawName  = $user->getRawOriginal('department');

        if ($departemen !== '') {
            $actualDeptName = strtolower(trim((string) (
                is_object($deptRelation) ? ($deptRelation->name ?? '') : ($deptRawName ?? '')
            )));
            if ($actualDeptName !== '' && $actualDeptName !== strtolower($departemen)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nama departemen tidak sesuai dengan akun karyawan!',
                ], 403);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $departmentPayload = is_object($deptRelation)
            ? [
                'id'   => $deptRelation->id,
                'code' => $deptRelation->code,
                'name' => $deptRelation->name,
            ]
            : $deptRawName;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => [
                'token' => $token,
                'user'  => [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'username'        => $user->username,
                    'email'           => $user->email,
                    'role'            => $user->role,
                    'department_id'   => $user->department_id,
                    'department_code' => is_object($deptRelation) ? $deptRelation->code : null,
                    'department'      => $departmentPayload,
                ],
            ],
        ]);
    }
}