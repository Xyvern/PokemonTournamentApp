<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CheckBanned
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::id()) {
            $user = User::withTrashed()->find(Auth::id());
            
            if ($user && $user->trashed() && $user->role != 2) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['error' => 'Your account has been deactivated by an Administrator.']);
            }
        }
        return $next($request);
    }
}