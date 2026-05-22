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

        // Catat ke Audit Trail (Gunakan format pendek konsisten)
        DB::table('audit_trails')->insert([
            'table_name'   => 'users',
            'action'       => 'INSERT', // Disingkat agar muat di VARCHAR(10/15)
            'record_id'    => $userId,
            'new_values'   => json_encode(['name' => $validated['name'], 'email' => $validated['email'], 'role' => $validated['role']]),
            'performed_by' => Auth::id(),
            'created_at'   => Carbon::now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna baru berhasil ditambahkan!');
    }

    // UPDATE: Memperbarui data pengguna (Nama, Email, Peran, & Kata Sandi Opsional)
    public function update(Request $request, $id)
    {
        // 1. Validasi input - password kita buat 'nullable' agar opsional
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150',
            'role'     => 'required|in:admin,user',
            'password' => 'nullable|min:6', 
        ]);

        $oldUser = DB::table('users')->where('id', $id)->first();
        if (!$oldUser) {
            return redirect()->back()->with('error', 'Data pengguna tidak ditemukan.');
        }

        // Cek duplikasi email (melompati pemeriksaan email milik pengguna itu sendiri)
        $emailExists = DB::table('users')
            ->where('email', $validated['email'])
            ->where('id', '!=', $id)
            ->exists();

        if ($emailExists) {
            return redirect()->back()->with('error', 'Alamat email ini sudah digunakan oleh akun lain!');
        }

        // 2. Susun data dasar pembaruan (Nama, Email, dan Peran)
        $updateData = [
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'role'       => $validated['role'],
            'updated_at' => Carbon::now(),
        ];

        $newValuesForAudit = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'role'  => $validated['role']
        ];

        // 3. KUNCI PERBAIKAN: Jika field password diisi, enkripsi dan masukkan ke array update
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
            $newValuesForAudit['password_changed'] = true;
        }

        // 4. Eksekusi pembaruan rekaman ke database
        DB::table('users')->where('id', $id)->update($updateData);

        // Catat aktivitas ke Audit Trail
        DB::table('audit_trails')->insert([
            'table_name'   => 'users',
            'action'       => 'UPDATE', // Disingkat mengikuti standar skema log insiden
            'record_id'    => $id,
            'old_values'   => json_encode(['name' => $oldUser->name, 'email' => $oldUser->email, 'role' => $oldUser->role]),
            'new_values'   => json_encode($newValuesForAudit),
            'performed_by' => Auth::id(),
            'created_at'   => Carbon::now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Data kredensial dan profil pengguna berhasil diperbarui!');
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
            'action'       => 'DELETE', // Disingkat agar seragam
            'record_id'    => $id,
            'old_values'   => json_encode(['name' => $oldUser->name, 'email' => $oldUser->email, 'role' => $oldUser->role]),
            'performed_by' => Auth::id(),
            'created_at'   => Carbon::now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna berhasil dihapus dari sistem!');
    }

    // ==========================================
    // METHOD UNTUK DOWNLOAD EXCEL (CSV STREAM)
    // ==========================================
    public function export()
    {
        $users = DB::table('users')->orderBy('created_at', 'desc')->get();
        $filename = 'Daftar_Pengguna_Sistem_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['ID User', 'Nama Lengkap', 'Email Akun', 'Peran Hak Akses', 'Waktu Pendaftaran']);

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

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    // FITUR TAMBAHAN: MODUL PENGATURAN KREDENSIAL AKUN MANDIRI
    // =========================================================================

    public function editProfile()
    {
        $user = Auth::user();
        return view('user.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150',
            'password' => 'nullable|min:6|confirmed', 
        ]);

        $emailExists = DB::table('users')
            ->where('email', $validated['email'])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailExists) {
            return redirect()->back()->with('error', 'Alamat email tersebut sudah terdaftar pada akun lain!');
        }

        $updateData = [
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'updated_at' => Carbon::now(),
        ];

        $newValuesForAudit = [
            'name'  => $validated['name'],
            'email' => $validated['email']
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
            $newValuesForAudit['password_changed'] = true;
        }

        DB::table('users')->where('id', $user->id)->update($updateData);

        // PERBAIKAN UTAMA: Mengubah string aksi dari 'SELF_UPDATE_PROFILE' menjadi 'UPDATE' 
        // agar langsung lolos validasi panjang kolom database (no data truncated)
        DB::table('audit_trails')->insert([
            'table_name'   => 'users',
            'action'       => 'UPDATE', 
            'record_id'    => $user->id,
            'old_values'   => json_encode(['name' => $user->name, 'email' => $user->email]),
            'new_values'   => json_encode($newValuesForAudit),
            'performed_by' => $user->id,
            'created_at'   => Carbon::now(),
        ]);

        return redirect()->back()->with('success', 'Profil dan kredensial akun Anda berhasil diperbarui!');
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            
            // Membuat nama berkas unik agar tidak bentrok di server file
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Simpan fisik file ke folder storage/app/public/avatars
            $file->storeAs('public/avatars', $filename);

            // 1. Update nama file ke database MySQL murni
            DB::table('users')->where('id', $user->id)->update([
                'avatar'     => $filename,
                'updated_at' => Carbon::now()
            ]);

            // 2. KUNCI PERBAIKAN UTAMA: Paksa Auth Laravel untuk memperbarui data session yang aktif saat ini
            if (auth()->check()) {
                auth()->user()->avatar = $filename;
            }

            // Catat aktivitas ke Audit Trail
            DB::table('audit_trails')->insert([
                'table_name'   => 'users',
                'action'       => 'UPDATE',
                'record_id'    => $user->id,
                'new_values'   => json_encode(['avatar_uploaded' => $filename]),
                'performed_by' => $user->id,
                'created_at'   => Carbon::now(),
            ]);

            return redirect()->back()->with('success', 'Foto profil Anda berhasil diperbarui dengan aman!');
        }

        return redirect()->back()->with('error', 'Gagal memproses unggahan gambar, silakan coba kembali.');
    }
}