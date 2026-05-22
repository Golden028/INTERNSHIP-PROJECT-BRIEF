<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IncidentController extends Controller
{
    // Tampilan Khusus User Lapangan (Hanya Form Lapor & Tabel Monitor biasa)
    public function index()
    {
        $incidents = DB::select("
            SELECT * FROM incident_logs 
            WHERE deleted_at IS NULL 
            ORDER BY 
                CASE WHEN severity_level = 'Critical' THEN 1 WHEN severity_level = 'Warning' THEN 2 ELSE 3 END ASC, 
                created_at DESC
        ");
        return view('incidents.index', compact('incidents'));
    }

    // Tampilan Khusus Admin (Bisa Edit Status & Punya Akses Tombol Aksi)
    public function adminIndex()
    {
        $incidents = DB::select("
            SELECT * FROM incident_logs 
            WHERE deleted_at IS NULL 
            ORDER BY 
                CASE WHEN severity_level = 'Critical' THEN 1 WHEN severity_level = 'Warning' THEN 2 ELSE 3 END ASC, 
                created_at DESC
        ");
        return view('admin.dashboard', compact('incidents'));
    }

    // Fungsi store, update, dan destroy tetap sama seperti kode sebelumnya...
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'severity_level' => 'required|in:Normal,Warning,Critical',
        ]);

        $incidentId = DB::table('incident_logs')->insertGetId([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'severity_level' => $validated['severity_level'],
            'status' => 'Open',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('audit_trails')->insert([
            'table_name' => 'incident_logs',
            'action' => 'INSERT',
            'record_id' => $incidentId,
            'new_values' => json_encode($validated),
            'created_at' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', 'Log insiden berhasil dicatat!');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:Open,In Progress,Resolved',
        ]);

        $oldData = DB::table('incident_logs')->where('id', $id)->first();

        DB::table('incident_logs')->where('id', $id)->update([
            'status' => $validated['status'],
            'updated_at' => Carbon::now(),
        ]);

        DB::table('audit_trails')->insert([
            'table_name' => 'incident_logs',
            'action' => 'UPDATE',
            'record_id' => $id,
            'old_values' => json_encode(['status' => $oldData->status]),
            'new_values' => json_encode(['status' => $validated['status']]),
            'created_at' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', 'Status insiden berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $oldData = DB::table('incident_logs')->where('id', $id)->whereNull('deleted_at')->first();

        DB::table('incident_logs')->where('id', $id)->update([
            'deleted_at' => Carbon::now()
        ]);

        DB::table('audit_trails')->insert([
            'table_name' => 'incident_logs',
            'action' => 'SOFT_DELETE',
            'record_id' => $id,
            'old_values' => json_encode($oldData),
            'created_at' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', 'Log insiden berhasil di-soft-delete!');
    }
}