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

        // Find the user, INCLUDING deactivated ones
        $user = User::withTrashed()->where('username', $request->username)->first();

        // Check if user exists and password is correct
        if ($user && Hash::check($request->password, $user->password)) {
            
            // Check if the account is deactivated
            if ($user->trashed()) {
                return back()->withErrors([
                    'username' => 'Your account has been deactivated by an administrator.',
                ]);
            }

            // Log them in
            Auth::login($user);
            $request->session()->regenerate();
            
            if (Auth::user()->role == 1) {
                return redirect()->route('player.home');
            } elseif (Auth::user()->role == 2) {
                return redirect()->route('admin.dashboard');
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
        return redirect('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}