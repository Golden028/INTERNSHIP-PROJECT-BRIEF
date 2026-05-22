<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\UserController;

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

    // =========================================================================
    // Menu Utama Log Insiden Bersama (Dapat diakses BERSAMA oleh Admin maupun User)
    // =========================================================================
    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
    Route::post('/incidents', [IncidentController::class, 'store'])->name('incidents.store');
    Route::get('/incidents/export', [IncidentController::class, 'export'])->name('incidents.export');
    Route::put('/incidents/{id}', [IncidentController::class, 'update'])->name('incidents.update');
    
    // PERBAIKAN UTAMA: Rute delete dipindah ke rute bersama agar User biasa bisa menghapus datanya sendiri
    Route::delete('/incidents/{id}', [IncidentController::class, 'destroy'])->name('incidents.destroy');

    // KELOMPOK HAK AKSES USER LAPANGAN
    Route::middleware('role:user')->prefix('user')->group(function () {
        Route::get('/dashboard', function () {
            return view('user.dashboard');
        })->name('incidents.dashboard');
    });

    // DATA PROFILE USER
    Route::middleware('auth')->group(function () {
    Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/upload-photo', [UserController::class, 'uploadPhoto'])->name('profile.upload_photo');
    });

    // KELOMPOK HAK AKSES ADMINISTRATOR
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Dashboard Beranda Admin
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');

        // Kontrol CRUD Manajemen Kelola Pengguna (Admin Only)
        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        Route::get('/users/export', [UserController::class, 'export'])->name('admin.users.export');
    });
});