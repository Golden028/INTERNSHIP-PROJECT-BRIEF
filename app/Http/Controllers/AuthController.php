<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function showLogin() {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Cari user dengan Raw Query Builder
        $user = DB::table('users')->where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            // Lakukan login manual ke session auth guard Laravel
            Auth::loginUsingId($user->id);
            $request->session()->regenerate();

            // Redirect berdasarkan peran (Role Redirection)
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('incidents.index');
        }

        return back()->withErrors(['email' => 'Email atau password salah!'])->onlyInput('email');
    }

    public function showRegister() {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'password' => 'required|min:6|confirmed',
        ]);

        // Cek jika email sudah terdaftar
        $exists = DB::table('users')->where('email', $validated['email'])->exists();
        if ($exists) {
            return back()->withErrors(['email' => 'Email ini sudah terdaftar di sistem Greenfields.']);
        }

        // Insert User Baru tanpa ORM
        $userId = DB::table('users')->insertGetId([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user', // Default pendaftaran adalah user lapangan
            'created_at' => Carbon::now(),
        ]);

        Auth::loginUsingId($userId);
        return redirect()->route('incidents.index')->with('success', 'Akun berhasil terdaftar!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}