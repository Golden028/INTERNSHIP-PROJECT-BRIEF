<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class IncidentController extends Controller
{
    // Tampilan Bersama: Dipakai oleh Route /incidents (Admin & User diarahkan ke file view yang sama)
    public function index()
    {
        // Mengambil log insiden yang belum dihapus, diurutkan berdasarkan Attention Logic Prioritas
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

        return view('incident.index', compact('incidents'));
    }

    // Prosedur Menyimpan Log Insiden Operasional Baru (Mendukung 4 Field Masukan + Auto Pelapor)
    public function store(Request $request)
    {
        // Validasi input data secara ketat sebelum dieksekusi oleh query database
        $validated = $request->validate([
            'room_id'        => 'required|in:Ruang 1,Ruang 2,Ruang 3,Ruang 4,Ruang 5',
            'title'          => 'required|string|max:150',
            'description'    => 'nullable|string',
            'severity_level' => 'required|in:Normal,Warning,Critical',
        ]);

        // Menyimpan masukan ke database menggunakan Raw Query Builder ramah memori RAM (NFR-1)
        $incidentId = DB::table('incident_logs')->insertGetId([
            'room_id'           => $validated['room_id'],
            'title'             => $validated['title'],
            'description'       => $validated['description'],
            'severity_level'    => $validated['severity_level'],
            'status'            => 'Open', // Status alur kerja default saat pertama kali dilaporkan
            'reported_by_name'  => Auth::user()->name, // Menangkap otomatis nama dari sesi user login aktif
            'created_at'        => Carbon::now(),
            'updated_at'        => Carbon::now(),
        ]);

        // Rekam aksi operasional penambahan data ke dalam sistem Audit Trail
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

    // Proses Memperbarui Status Penanganan Kendala Lapangan (Akses Terbatas: Khusus Admin)
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:Open,In Progress,Resolved',
        ]);

        // Mengambil rekaman data lama untuk kebutuhan komparasi histori log audit
        $oldData = DB::table('incident_logs')->where('id', $id)->first();

        if (!$oldData) {
            return redirect()->route('incidents.index')->with('error', 'Rekaman log tidak ditemukan.');
        }

        // Eksekusi pembaruan status kerja
        DB::table('incident_logs')->where('id', $id)->update([
            'status'     => $validated['status'],
            'updated_at' => Carbon::now(),
        ]);

        // Catat mutasi perubahan status ke dalam Audit Trail
        DB::table('audit_trails')->insert([
            'table_name' => 'incident_logs',
            'action'     => 'UPDATE',
            'record_id'  => $id,
            'old_values' => json_encode(['status' => $oldData->status]),
            'new_values' => json_encode(['status' => $validated['status']]),
            'performed_by'=> Auth::id(),
            'created_at' => Carbon::now(),
        ]);

        return redirect()->route('incidents.index')->with('success', 'Status penanganan insiden berhasil diperbarui!');
    }

    // Mekanisme Penghapusan Data Aman (Soft-Delete Mechanism - Akses Terbatas: Khusus Admin)
    public function destroy($id)
    {
        $oldData = DB::table('incident_logs')->where('id', $id)->whereNull('deleted_at')->first();

        if (!$oldData) {
            return redirect()->route('incidents.index')->with('error', 'Log gagal dihapus atau sudah diamankan.');
        }

        // Mengubah visibilitas data dengan mengisi timestamp deleted_at tanpa membuang baris fisik SQL
        DB::table('incident_logs')->where('id', $id)->update([
            'deleted_at' => Carbon::now()
        ]);

        // Rekam jejak aksi pemusnahan visibilitas ke tabel audit trail
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
}