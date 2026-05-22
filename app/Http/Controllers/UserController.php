<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UserController extends Controller
{
    // READ: Menampilkan daftar semua pengguna
    public function index()
    {
        $users = DB::select("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
        return view('admin.users', compact('users'));
    }

    // CREATE: Menambahkan pengguna/staf operasional baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150',
            'password' => 'required|min:6',
            'role'     => 'required|in:admin,user',
        ]);

        // Cek duplikasi email
        $exists = DB::table('users')->where('email', $validated['email'])->exists();
        if ($exists) {
            return redirect()->back()->with('error', 'Email ini sudah terdaftar di sistem!');
        }

        // Simpan menggunakan Query Builder murni
        $userId = DB::table('users')->insertGetId([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'role'       => $validated['role'],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Catat ke Audit Trail
        DB::table('audit_trails')->insert([
            'table_name'   => 'users',
            'action'       => 'INSERT_USER',
            'record_id'    => $userId,
            'new_values'   => json_encode(['name' => $validated['name'], 'email' => $validated['email'], 'role' => $validated['role']]),
            'performed_by' => Auth::id(),
            'created_at'   => Carbon::now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna baru berhasil ditambahkan!');
    }

    // UPDATE: Memperbarui data pengguna (Nama, Email, & Role)
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'role' => 'required|in:admin,user',
        ]);

        $oldUser = DB::table('users')->where('id', $id)->first();
        if (!$oldUser) {
            return redirect()->back()->with('error', 'Data pengguna tidak ditemukan.');
        }

        // Eksekusi Update data
        DB::table('users')->where('id', $id)->update([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'role'       => $validated['role'],
            'updated_at' => Carbon::now(),
        ]);

        // Catat aktivitas ke Audit Trail
        DB::table('audit_trails')->insert([
            'table_name'   => 'users',
            'action'       => 'UPDATE_USER',
            'record_id'    => $id,
            'old_values'   => json_encode(['name' => $oldUser->name, 'email' => $oldUser->email, 'role' => $oldUser->role]),
            'new_values'   => json_encode($validated),
            'performed_by' => Auth::id(),
            'created_at'   => Carbon::now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Data pengguna berhasil diperbarui!');
    }

    // DELETE: Menghapus pengguna secara permanen dari sistem
    public function destroy($id)
    {
        // Mencegah admin menghapus dirinya sendiri secara tidak sengaja
        if (Auth::id() == $id) {
            return redirect()->back()->with('error', 'Akses ditolak! Anda tidak bisa menghapus akun Anda sendiri yang sedang aktif.');
        }

        $oldUser = DB::table('users')->where('id', $id)->first();
        if (!$oldUser) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }

        // Hapus baris data murni SQL
        DB::table('users')->where('id', $id)->delete();

        // Rekam jejak audit pemusnahan user
        DB::table('audit_trails')->insert([
            'table_name'   => 'users',
            'action'       => 'DELETE_USER',
            'record_id'    => $id,
            'old_values'   => json_encode(['name' => $oldUser->name, 'email' => $oldUser->email, 'role' => $oldUser->role]),
            'performed_by' => Auth::id(),
            'created_at'   => Carbon::now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna berhasil dihapus dari sistem!');
    }

    // ==========================================
    // TAMBAHAN BARU: METHOD UNTUK DOWNLOAD EXCEL (CSV STREAM)
    // ==========================================
    public function export()
    {
        // Ambil seluruh data pengguna dari tabel users, diurutkan dari yang paling baru terdaftar
        $users = DB::table('users')->orderBy('created_at', 'desc')->get();
        $filename = 'Daftar Pengguna Sistem ' . date('Ymd His') . '.csv';

        // Set Headers HTTP untuk memaksa file diunduh langsung sebagai file Excel/CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        // Definisikan proses stream data agar hemat RAM server
        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // Masukkan Byte Order Mark (BOM) UTF-8 agar Excel langsung membaca separator koma dan teks tanpa berantakan
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Set Header baris judul pertama di spreadsheet Excel
            fputcsv($file, ['ID User', 'Nama Lengkap', 'Email Akun', 'Peran Hak Akses', 'Waktu Pendaftaran']);

            // Looping data pengguna, konversi created_at ke format string pendek ramah ukuran kolom excel (no ######)
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role === 'admin' ? 'Administrator' : 'User (Staf Lapangan)',
                    $user->created_at ? date('d-m-Y H:i', strtotime($user->created_at)) : '-'
                ]);
            }
            
            fclose($file);
        };

        // Kembalikan stream response ke browser
        return response()->stream($callback, 200, $headers);
    }
}