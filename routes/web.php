<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IncidentController;

// Rute Tamu (Belum Login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Rute Terproteksi (Wajib Login)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Halaman Utama User Lapangan (Bisa akses form lapor, baca log insiden)
    Route::get('/', [IncidentController::class, 'index'])->name('incidents.index');
    Route::post('/incidents', [IncidentController::class, 'store'])->name('incidents.store');

    // Halaman Manajemen Khusus Akun Admin (Menggunakan Middleware Role Admin)
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [IncidentController::class, 'index'])->name('admin.dashboard'); // Gunakan index/custom admin panel
        Route::put('/incidents/{id}', [IncidentController::class, 'update'])->name('incidents.update');
        Route::delete('/incidents/{id}', [IncidentController::class, 'destroy'])->name('incidents.destroy');
    });
});