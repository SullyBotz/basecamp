<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LogUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            // Set status online selama 5 menit ke depan setiap kali user memuat halaman
            $expiresAt = now()->addMinutes(5); 
            Cache::put('user-is-online-' . Auth::user()->id, true, $expiresAt);
        }

        return $next($request);
    }
}