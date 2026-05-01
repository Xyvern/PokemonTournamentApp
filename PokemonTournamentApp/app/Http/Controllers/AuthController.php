<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function doLogin(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::withTrashed()->where('username', $request->username)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            
            if ($user->trashed()) {
                return back()->withErrors([
                    'username' => 'Your account has been deactivated by an administrator.',
                ]);
            }

            Auth::login($user);
            $request->session()->regenerate();
            
            if (Auth::user()->role == 1) {
                // ADDED SUCCESS TOAST
                return redirect()->route('player.home')->with('success', 'Welcome back, ' . $user->nickname . '!');
            } elseif (Auth::user()->role == 2) {
                // ADDED SUCCESS TOAST
                return redirect()->route('admin.dashboard')->with('success', 'Admin login successful.');
            }
        }

        return back()->withErrors([
            'username' => 'Invalid credentials provided.',
        ]);
    }

    public function register()
    {
        return view('auth.register');
    }

    public function doRegister(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:users,username',
            'nickname' => 'required|string|max:255',
            'password' => 'required|confirmed|min:6',
        ]);

        User::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'nickname' => $validated['nickname'],
            'role' => 1,
            'elo' => 1000,
            'matches_played' => 0,
            'matches_won' => 0,
            'matches_lost' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // ADDED SUCCESS TOAST
        return redirect('/login')->with('success', 'Account created successfully! Please log in.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // ADDED SUCCESS TOAST
        return redirect('/login')->with('success', 'You have been successfully logged out.');
    }
}