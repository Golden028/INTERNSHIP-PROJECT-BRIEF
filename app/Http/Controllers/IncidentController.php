<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class IncidentController extends Controller
{
    // Tampilan Bersama: Dipakai oleh Route /incidents (Admin melihat semua, User hanya melihat miliknya)
    public function index()
    {
        // 1. Cek hak akses role user yang sedang login
        if (Auth::user()->role === 'admin') {
            // Admin: Mengambil semua log insiden yang belum dihapus
            $incidents = DB::select("
                SELECT * FROM incident_logs 
                WHERE deleted_at IS NULL 
                ORDER BY 
                    CASE 
                        WHEN severity_level = 'Critical' THEN 1 
                        WHEN severity_level = 'Warning' THEN 2 
                        ELSE 3 
                    END ASC, 
                    created_at DESC
            ");
        } else {
            // User Biasa: Hanya mengambil log insiden yang dibuat oleh dirinya sendiri
            $incidents = DB::select("
                SELECT * FROM incident_logs 
                WHERE deleted_at IS NULL AND performed_by = ?
                ORDER BY 
                    CASE 
                        WHEN severity_level = 'Critical' THEN 1 
                        WHEN severity_level = 'Warning' THEN 2 
                        ELSE 3 
                    END ASC, 
                    created_at DESC
            ", [Auth::id()]);
        }

        return view('incident.index', compact('incidents'));
    }

    // Prosedur Menyimpan Log Insiden Operasional Baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id'        => 'required|in:Ruang 1,Ruang 2,Ruang 3,Ruang 4,Ruang 5',
            'title'          => 'required|string|max:150',
            'description'    => 'nullable|string',
            'severity_level' => 'required|in:Normal,Warning,Critical',
        ]);

        // Menyimpan masukan ke database beserta pengenal user ('performed_by')
        $incidentId = DB::table('incident_logs')->insertGetId([
            'room_id'           => $validated['room_id'],
            'title'             => $validated['title'],
            'description'       => $validated['description'],
            'severity_level'    => $validated['severity_level'],
            'status'            => 'Open', 
            'reported_by_name'  => Auth::user()->name, 
            'performed_by'      => Auth::id(), 
            'created_at'        => \Carbon\Carbon::now(),
            'updated_at'        => \Carbon\Carbon::now(),
        ]);

        // Rekam ke Audit Trail
        DB::table('audit_trails')->insert([
            'table_name'  => 'incident_logs',
            'action'      => 'INSERT',
            'record_id'   => $incidentId,
            'new_values'  => json_encode(array_merge($validated, ['reported_by_name' => Auth::user()->name, 'status' => 'Open'])),
            'performed_by'=> Auth::id(),
            'created_at'  => Carbon::now(),
        ]);

        return redirect()->route('incidents.index')->with('success', 'Log kendala operasional berhasil dicatatkan!');
    }

    // Proses Memperbarui Data
    public function update(Request $request, $id)
    {
        $oldData = DB::table('incident_logs')->where('id', $id)->whereNull('deleted_at')->first();

        if (!$oldData) {
            return redirect()->route('incidents.index')->with('error', 'Rekaman log tidak ditemukan.');
        }

        if (Auth::user()->role !== 'admin' && $oldData->performed_by !== Auth::id()) {
            return redirect()->route('incidents.index')->with('error', 'Anda tidak memiliki hak akses untuk mengubah data ini.');
        }

        if (Auth::user()->role === 'admin' && !$request->has('title')) {
            $validated = $request->validate([
                'status' => 'required|in:Open,In Progress,Resolved',
            ]);

            $updateData = [
                'status'     => $validated['status'],
                'updated_at' => Carbon::now(),
            ];

            $newValues = ['status' => $validated['status']];
            $oldValues = ['status' => $oldData->status];
        } else {
            $validated = $request->validate([
                'room_id'        => 'required|in:Ruang 1,Ruang 2,Ruang 3,Ruang 4,Ruang 5',
                'title'          => 'required|string|max:150',
                'description'    => 'nullable|string',
                'severity_level' => 'required|in:Normal,Warning,Critical',
            ]);

            $updateData = [
                'room_id'        => $validated['room_id'],
                'title'          => $validated['title'],
                'description'    => $validated['description'],
                'severity_level' => $validated['severity_level'],
                'updated_at'     => Carbon::now(),
            ];

            $newValues = $validated;
            $oldValues = [
                'room_id'        => $oldData->room_id,
                'title'          => $oldData->title,
                'description'    => $oldData->description,
                'severity_level' => $oldData->severity_level,
            ];
        }

        DB::table('incident_logs')->where('id', $id)->update($updateData);

        DB::table('audit_trails')->insert([
            'table_name' => 'incident_logs',
            'action'     => 'UPDATE',
            'record_id'  => $id,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($newValues),
            'performed_by'=> Auth::id(),
            'created_at' => Carbon::now(),
        ]);

        return redirect()->route('incidents.index')->with('success', 'Log insiden berhasil diperbarui!');
    }

    // Mekanisme Penghapusan Data Aman
    public function destroy($id)
    {
        $oldData = DB::table('incident_logs')->where('id', $id)->whereNull('deleted_at')->first();

        if (!$oldData) {
            return redirect()->route('incidents.index')->with('error', 'Log gagal dihapus atau sudah diamankan.');
        }

        if (Auth::user()->role !== 'admin' && $oldData->performed_by !== Auth::id()) {
            return redirect()->route('incidents.index')->with('error', 'Anda tidak memiliki hak akses untuk menghapus data ini.');
        }

        DB::table('incident_logs')->where('id', $id)->update([
            'deleted_at' => Carbon::now()
        ]);

        DB::table('audit_trails')->insert([
            'table_name' => 'incident_logs',
            'action'     => 'SOFT_DELETE',
            'record_id'  => $id,
            'old_values' => json_encode($oldData),
            'performed_by'=> Auth::id(),
            'created_at' => Carbon::now(),
        ]);

        return redirect()->route('incidents.index')->with('success', 'Log insiden berhasil di-soft-delete dengan aman!');
    }

    // ==========================================
    // TAMBAHAN BARU: METHOD UNTUK DOWNLOAD EXCEL (CSV)
    // ==========================================
    public function export()
    {
        // 1. Ambil data sesuai dengan role yang sedang aktif
        if (Auth::user()->role === 'admin') {
            $data = DB::table('incident_logs')->whereNull('deleted_at')->orderBy('created_at', 'desc')->get();
            $filename = 'Log Insiden ' . date('Ymd His') . '.csv';
        } else {
            $data = DB::table('incident_logs')->whereNull('deleted_at')->where('performed_by', Auth::id())->orderBy('created_at', 'desc')->get();
            $filename = 'Log Insiden Saya ' . date('Ymd His') . '.csv';
        }

        // 2. Tentukan Header Kolom Excel
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        // 3. Callback Stream data untuk menghemat kapasitas RAM web server
        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Tambahkan BOM (Byte Order Mark) agar Excel mendeteksi encoding UTF-8 dengan benar (menghindari text berantakan)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Judul kolom lembar kerja
            fputcsv($file, ['ID Log', 'ID Ruang', 'Judul Masalah / Kendala', 'Deskripsi Kronologi', 'Tingkat Keparahan', 'Status Kerja', 'Nama Pelapor', 'Waktu Kejadian']);

            // Isikan baris data mentah dari database
            foreach ($data as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->room_id ?? 'Ruang 1',
                    $row->title,
                    $row->description ?? 'Tidak ada catatan.',
                    $row->severity_level,
                    $row->status,
                    $row->reported_by_name,
                    $row->created_at
                ]);
            }
            
            fclose($file);
        };

        // Return data dalam bentuk file unduhan instan
        return response()->stream($callback, 200, $headers);
    }
}