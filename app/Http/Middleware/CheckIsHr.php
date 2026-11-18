<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIsHr
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Sesuaikan pengecekan role atau department user sesuai struktur database Anda
        if ($user && ($user->role === 'HR' || $user->department === 'HR' || $user->is_hr === true)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Akses ini khusus untuk HR Management.',
        ], 403);
    }
}