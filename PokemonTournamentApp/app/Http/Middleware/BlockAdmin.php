<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BlockAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // If the user is logged in AND is an admin (role == 2)
        if (Auth::check() && Auth::user()->role == 2) {
            // Kick them back to the admin dashboard
            return redirect()->route('admin.dashboard')->with('error', 'Admins cannot access the player portal.');
        }

        return $next($request);
    }
}