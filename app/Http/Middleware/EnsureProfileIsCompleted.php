<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Pastikan user sudah login
        if (auth()->check()) {
            $user = auth()->user();
            
            // 2. Daftar role yang TIDAK WAJIB mengisi form profil awal
            $excludedRoles = ['admin', 'dekan', 'kaprodi'];

            // 3. Jika role user WAJIB isi (mahasiswa/alumni) DAN is_profile_completed masih false (0)
            if (!in_array($user->role, $excludedRoles) && !$user->is_profile_completed) {
                
                // 4. Cegah error looping "Too Many Redirects"
                // Jika user sudah berada di halaman pengisian profil, biarkan lewat
                if ($request->routeIs('profile.complete') || $request->routeIs('logout')) {
                    return $next($request);
                }

                // 5. Lempar ke halaman pengisian profil
                return redirect()->route('profile.complete')
                    ->with('warning', 'Silakan lengkapi data angkatan dan akademik Anda terlebih dahulu.');
            }
        }

        // Jika profil sudah lengkap atau role adalah admin/dekan, biarkan lewat
        return $next($request);
    }
}