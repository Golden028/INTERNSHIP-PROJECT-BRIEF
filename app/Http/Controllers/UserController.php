<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UserController extends Controller
{
    // Tentukan ID Head Admin yang tidak boleh diubah/dihapus oleh admin lain
    private const HEAD_ADMIN_ID = 1; 

    // READ: Menampilkan daftar semua pengguna
    public function index()
    {
        $users = DB::select("SELECT id, name, email, role, created_at FROM users ORDER BY created_at ASC");
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

        $exists = DB::table('users')->where('email', $validated['email'])->exists();
        if ($exists) {
            return redirect()->back()->with('error', 'Email ini sudah terdaftar di sistem!');
        }

        $userId = DB::table('users')->insertGetId([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'role'       => $validated['role'],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('audit_trails')->insert([
            'table_name'   => 'users',
            'action'       => 'INSERT', 
            'record_id'    => $userId,
            'new_values'   => json_encode(['name' => $validated['name'], 'email' => $validated['email'], 'role' => $validated['role']]),
            'performed_by' => Auth::id(),
            'created_at'   => Carbon::now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna baru berhasil ditambahkan!');
    }

    // UPDATE: Memperbarui data pengguna
    public function update(Request $request, $id)
    {
        // Proteksi: Admin lain tidak bisa mengubah akun Head Admin
        if ($id == self::HEAD_ADMIN_ID && Auth::id() != self::HEAD_ADMIN_ID) {
            return redirect()->back()->with('error', 'Akses ditolak! Anda tidak diizinkan mengubah akun Head Admin.');
        }

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

        $emailExists = DB::table('users')
            ->where('email', $validated['email'])
            ->where('id', '!=', $id)
            ->exists();

        if ($emailExists) {
            return redirect()->back()->with('error', 'Alamat email ini sudah digunakan oleh akun lain!');
        }

        $updateData = [
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'role'       => $validated['role'],
            'updated_at' => Carbon::now(),
        ];

        $newValuesForAudit = ['name' => $validated['name'], 'email' => $validated['email'], 'role' => $validated['role']];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
            $newValuesForAudit['password_changed'] = true;
        }

        DB::table('users')->where('id', $id)->update($updateData);

        DB::table('audit_trails')->insert([
            'table_name'   => 'users',
            'action'       => 'UPDATE', 
            'record_id'    => $id,
            'old_values'   => json_encode(['name' => $oldUser->name, 'email' => $oldUser->email, 'role' => $oldUser->role]),
            'new_values'   => json_encode($newValuesForAudit),
            'performed_by' => Auth::id(),
            'created_at'   => Carbon::now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Data berhasil diperbarui!');
    }

    // DELETE: Menghapus pengguna
    public function destroy($id)
    {
        // Proteksi: Tidak boleh hapus Head Admin
        if ($id == self::HEAD_ADMIN_ID) {
            return redirect()->back()->with('error', 'Akses ditolak! Akun Head Admin tidak dapat dihapus.');
        }

        // Proteksi: Tidak boleh hapus diri sendiri
        if (Auth::id() == $id) {
            return redirect()->back()->with('error', 'Akses ditolak! Anda tidak bisa menghapus akun Anda sendiri.');
        }

        $oldUser = DB::table('users')->where('id', $id)->first();
        if (!$oldUser) {
            return redirect()->back()->with('error', 'Pengguna tidak ditemukan.');
        }

        DB::table('users')->where('id', $id)->delete();

        DB::table('audit_trails')->insert([
            'table_name'   => 'users',
            'action'       => 'DELETE', 
            'record_id'    => $id,
            'old_values'   => json_encode(['name' => $oldUser->name, 'email' => $oldUser->email, 'role' => $oldUser->role]),
            'performed_by' => Auth::id(),
            'created_at'   => Carbon::now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna berhasil dihapus!');
    }

    // METHOD UNTUK DOWNLOAD EXCEL (CSV STREAM)
    public function export()
    {
        $users = DB::table('users')->orderBy('created_at', 'asc')->get();
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

        // 1. Cek duplikasi email (abaikan milik user itu sendiri)
        $emailExists = DB::table('users')
            ->where('email', $validated['email'])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailExists) {
            return redirect()->back()->with('error', 'Alamat email tersebut sudah terdaftar pada akun lain!');
        }

        // 2. LOGIKA BARU: Cek apakah data benar-benar berubah
        $isNameChanged = ($validated['name'] !== $user->name);
        $isEmailChanged = ($validated['email'] !== $user->email);
        $isPasswordChanged = $request->filled('password');

        // Jika tidak ada satu pun data yang berubah, kembalikan tanpa session 'success'
        if (!$isNameChanged && !$isEmailChanged && !$isPasswordChanged) {
            return redirect()->back(); // Tidak mengirim notifikasi
        }

        // 3. Persiapkan data update
        $updateData = [
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'updated_at' => Carbon::now(),
        ];

        $newValuesForAudit = [
            'name'  => $validated['name'],
            'email' => $validated['email']
        ];

        if ($isPasswordChanged) {
            $updateData['password'] = Hash::make($validated['password']);
            $newValuesForAudit['password_changed'] = true;
        }

        // 4. Update ke Database
        DB::table('users')->where('id', $user->id)->update($updateData);

        // 5. Sinkronisasi data session Auth aktif secara real-time
        $freshUser = DB::table('users')->where('id', $user->id)->first();
        if (auth()->check()) {
            auth()->user()->name = $freshUser->name;
            auth()->user()->email = $freshUser->email;
            auth()->user()->avatar = $freshUser->avatar;
        }

        // 6. Catat audit trail
        DB::table('audit_trails')->insert([
            'table_name'   => 'users',
            'action'       => 'UPDATE', 
            'record_id'    => $user->id,
            'old_values'   => json_encode(['name' => $user->name, 'email' => $user->email]),
            'new_values'   => json_encode($newValuesForAudit),
            'performed_by' => $user->id,
            'created_at'   => Carbon::now(),
        ]);

        // 7. Kirim notifikasi sukses hanya jika ada perubahan
        return redirect()->back()->with('success', 'Profil dan kredensial akun Anda berhasil diperbarui!');
    }
    
    // =========================================================================
    // PERBAIKAN: MENYEMATKAN KEMBALI METHOD UPLOADPHOTO YANG TADI HILANG
    // =========================================================================
    public function uploadPhoto(Request $request)
    {
        // Mendukung file berukuran besar hingga 20MB (20480)
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:20480',
        ]);

        $user = Auth::user();

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            $publicPath = public_path('avatars');
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0777, true);
            }

            // KUNCI CLEANUP: Buang fisik file foto lama dari folder public/avatars agar tidak menumpuk
            if (!empty($user->avatar)) {
                $oldFilePath = $publicPath . '/' . $user->avatar;
                if (file_exists($oldFilePath)) {
                    @unlink($oldFilePath); 
                }
            }

            // Pindahkan file gambar baru langsung ke root publik
            $file->move($publicPath, $filename);

            // Update nama berkas file baru ke database MySQL
            DB::table('users')->where('id', $user->id)->update([
                'avatar'     => $filename,
                'updated_at' => Carbon::now()
            ]);

            // Sinkronisasi data session Auth aktif secara real-time untuk topbar
            if (auth()->check()) {
                auth()->user()->avatar = $filename;
            }

            // Catat log aktivitas
            DB::table('audit_trails')->insert([
                'table_name'   => 'users',
                'action'       => 'UPDATE',
                'record_id'    => $user->id,
                'new_values'   => json_encode(['avatar_uploaded' => $filename]),
                'performed_by' => $user->id,
                'created_at'   => Carbon::now(),
            ]);

            return redirect()->back()->with('success', 'Foto profil Anda berhasil diperbarui dan foto lama telah dihapus!');
        }

        return redirect()->back()->with('error', 'Gagal memproses unggahan gambar, silakan coba kembali.');
    }

    /**
     * Menghapus foto profil aktif dan mengembalikan tampilan ke inisial nama awal.
     */
    public function deletePhoto()
    {
        $user = Auth::user();

        if (!empty($user->avatar)) {
            $filePath = public_path('avatars/' . $user->avatar);

            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            DB::table('users')->where('id', $user->id)->update([
                'avatar'     => null,
                'updated_at' => Carbon::now()
            ]);

            if (auth()->check()) {
                auth()->user()->avatar = null;
            }

            DB::table('audit_trails')->insert([
                'table_name'   => 'users',
                'action'       => 'UPDATE',
                'record_id'    => $user->id,
                'new_values'   => json_encode(['avatar_removed' => true]),
                'performed_by' => $user->id,
                'created_at'   => Carbon::now(),
            ]);

            return redirect()->back()->with('success', 'Foto profil Anda berhasil dihapus, tampilan kembali ke inisial!');
        }

        return redirect()->back()->with('error', 'Anda tidak memiliki foto profil aktif untuk dihapus.');
    }
}