<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Kolom database yang diizinkan untuk pengisian massal.
     * PERBAIKAN: Masukkan 'role' agar sinkron saat pengecekan hak akses login
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * Kolom yang harus disembunyikan saat serialisasi objek data.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Menentukan konversi/casting tipe data otomatis dari database.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Memastikan framework mengenali enkripsi password baru
        ];
    }
}