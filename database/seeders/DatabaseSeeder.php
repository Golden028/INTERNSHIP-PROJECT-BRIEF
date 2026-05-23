<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema; // WAJIB TAMBAHKAN INI
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Matikan pengecekan foreign key constraint sementara
        Schema::disableForeignKeyConstraints();

        // Kosongkan tabel users terlebih dahulu (Sekarang aman dari error 1701)
        DB::table('users')->truncate();

        // Akun Administrator
        DB::table('users')->insert([
            'name' => 'Administrator Greenfields',
            'email' => 'admin@greenfields.com',
            'password' => Hash::make('123123123'),
            'role' => 'admin',
            'created_at' => Carbon::now(),
        ]);

        // Akun User Staf Lapangan
        DB::table('users')->insert([
            'name' => 'Yuda Staf Lapangan',
            'email' => 'yuda@greenfields.com',
            'password' => Hash::make('123123123'),
            'role' => 'user',
            'created_at' => Carbon::now(),
        ]);

        // 2. Hidupkan kembali pengecekan foreign key constraint
        Schema::enableForeignKeyConstraints();
    }
}