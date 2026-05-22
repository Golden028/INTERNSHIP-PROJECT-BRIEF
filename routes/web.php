<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IncidentController;

// Rute root '/' langsung mendeteksi status login tanpa perantara middleware internal
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->role === 'admin' 
            ? redirect()->route('admin.dashboard') 
            : redirect()->route('incidents.dashboard');
    }
    return redirect()->route('login');
})->name('home');

// Kelompok Tamu (Belum Login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Kelompok Terproteksi (Wajib Sesi Login Aktif)
Route::middleware('auth')->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Menu Utama Log Insiden Bersama (Dapat diakses Admin maupun User)
    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
    Route::post('/incidents', [IncidentController::class, 'store'])->name('incidents.store');

    // KELOMPOK HAK AKSES USER LAPANGAN
    Route::middleware('role:user')->prefix('user')->group(function () {
        Route::get('/dashboard', function () {
            return view('user.dashboard');
        })->name('incidents.dashboard');
    });

    // KELOMPOK HAK AKSES ADMINISTRATOR
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');
        
        Route::put('/incidents/{id}', [IncidentController::class, 'update'])->name('incidents.update');
        Route::delete('/incidents/{id}', [IncidentController::class, 'destroy'])->name('incidents.destroy');
    });
});