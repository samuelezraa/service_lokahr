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
        $user = $request->user()?->loadMissing('department');

        if ($user) {
            $deptCode = strtoupper(trim((string) ($user->department?->code ?? '')));
            $deptName = strtolower(trim((string) ($user->department?->name ?? $user->getRawOriginal('department') ?? '')));

            $isHrDepartment = $deptCode === 'DP002'
                || in_array($deptName, ['hr', 'hrd', 'hr management', 'human resource', 'human resources'], true);

            if ($isHrDepartment || $user->is_hr === true || strtoupper((string) $user->role) === 'HR') {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Akses ini khusus untuk HR Management.',
        ], 403);
    }
}