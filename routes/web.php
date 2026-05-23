<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AuditTrailController;

// Root redirect
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
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
});

// Kelompok Terproteksi (Wajib Sesi Login Aktif)
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // =========================================================================
    // NOTIFIKASI (semua role yang sudah login)
    // =========================================================================
    Route::get('/notifications/fetch',    [NotificationController::class, 'fetch'])->name('notifications.fetch');
    Route::post('/notifications/read/{id}', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read_all');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.page');

    // =========================================================================
    // Log Insiden Bersama (Admin & User)
    // =========================================================================
    Route::get('/incidents',         [IncidentController::class, 'index'])->name('incidents.index');
    Route::post('/incidents',        [IncidentController::class, 'store'])->name('incidents.store');
    Route::get('/incidents/export',  [IncidentController::class, 'export'])->name('incidents.export');
    Route::put('/incidents/{id}',    [IncidentController::class, 'update'])->name('incidents.update');
    Route::delete('/incidents/{id}', [IncidentController::class, 'destroy'])->name('incidents.destroy');

    // Dashboard User Lapangan
    Route::middleware('role:user')->prefix('user')->group(function () {
        Route::get('/dashboard', [IncidentController::class, 'userDashboard'])->name('incidents.dashboard');
    });

    // Profil Akun (semua role)
    Route::get('/profile/edit',            [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile/update',          [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/upload-photo',   [UserController::class, 'uploadPhoto'])->name('profile.upload_photo');
    Route::delete('/profile/delete-photo', [UserController::class, 'deletePhoto'])->name('profile.delete_photo');

    // =========================================================================
    // Admin Only
    // =========================================================================
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', fn () => view('admin.dashboard'))->name('admin.dashboard');
        Route::get('/dashboard/stats', [IncidentController::class, 'dashboardStats'])->name('admin.dashboard.stats');

        Route::get('/users',         [UserController::class, 'index'])->name('admin.users.index');
        Route::post('/users',        [UserController::class, 'store'])->name('admin.users.store');
        Route::put('/users/{id}',    [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        Route::get('/users/export',  [UserController::class, 'export'])->name('admin.users.export');

        Route::get('/audit-trails', [AuditTrailController::class, 'index'])->name('admin.audit.index');
        Route::get('/audit-trails/export', [AuditTrailController::class, 'export'])->name('admin.audit.export');
    });
});