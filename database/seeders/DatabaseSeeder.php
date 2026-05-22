<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Kosongkan tabel users terlebih dahulu
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
            'email' => 'user@greenfields.com',
            'password' => Hash::make('123123123'),
            'role' => 'user',
            'created_at' => Carbon::now(),
        ]);
    }
}