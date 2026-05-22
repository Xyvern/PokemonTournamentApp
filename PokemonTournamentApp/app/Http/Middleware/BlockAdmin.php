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
        if (Auth::check() && Auth::user()->role == 2) {
            return redirect()->route('admin.dashboard')->with('error', 'Admins cannot access the player portal.');
        }

        return $next($request);
    }
}