<?php

namespace App\Http\Controllers;



use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Cek apakah user login menggunakan username atau email
        $user = User::where('username', $request->username)
                    ->orWhere('email', $request->username)
                    ->first();

        // Verifikasi user dan password
       // Verifikasi user dan password
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password yang Anda masukkan salah!',
            ], 401);
        }

        // 🟢 Aktifkan pembuatan token Sanctum di sini
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => [
                'user'  => $user,
                'token' => $token, // 🟢 Sertakan token ke dalam response JSON
            ]
        ]);
    }
}