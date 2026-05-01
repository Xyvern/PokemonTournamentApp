<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // 2. Swap auth()-> for Auth::
        if (Auth::check() && Auth::user()->role == 2) {
            return $next($request);
        }

        return redirect('/')->with('error', 'You do not have permission to access the admin area.');
    }
}