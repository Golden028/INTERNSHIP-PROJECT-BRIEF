<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Helpers\NotificationHelper;

class IncidentController extends Controller
{
    // Tampilan Bersama: Dipakai oleh Route /incidents
    public function index()
    {
        if (Auth::user()->role === 'admin') {
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

        $incidentId = DB::table('incident_logs')->insertGetId([
            'room_id'           => $validated['room_id'],
            'title'             => $validated['title'],
            'description'       => $validated['description'],
            'severity_level'    => $validated['severity_level'],
            'status'            => 'Open',
            'reported_by_name'  => Auth::user()->name,
            'performed_by'      => Auth::id(),
            'created_at'        => Carbon::now(),
            'updated_at'        => Carbon::now(),
        ]);

        // Rekam ke Audit Trail
        DB::table('audit_trails')->insert([
            'table_name'   => 'incident_logs',
            'action'       => 'INSERT', 
            'record_id'    => $incidentId,
            // KARENA DATA BARU: old_values diisi null, dan new_values mengambil dari data yang divalidasi
            'old_values'   => null, 
            'new_values'   => json_encode(array_merge($validated, ['reported_by_name' => Auth::user()->name, 'status' => 'Open'])),
            'performed_by' => Auth::id(), 
            'created_at'   => Carbon::now(),
        ]);

        // ── NOTIFIKASI ─────────────────────────────────────────────────────────
        // 1. Beritahu semua ADMIN ada laporan baru
        NotificationHelper::notifyAdminNewIncident(
            $incidentId,
            $validated['title'],
            $validated['room_id'],
            $validated['severity_level'],
            Auth::user()->name
        );

        // 2. Beritahu USER SENDIRI bahwa laporannya berhasil masuk
        NotificationHelper::notifyUserOwnIncidentCreated(
            Auth::id(),
            $incidentId,
            $validated['title'],
            $validated['room_id'],
            $validated['severity_level']
        );
        // ───────────────────────────────────────────────────────────────────────

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

        // ── ADMIN: update status saja ─────────────────────────────────────────
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

            DB::table('incident_logs')->where('id', $id)->update($updateData);

            DB::table('audit_trails')->insert([
                'table_name'  => 'incident_logs',
                'action'      => 'UPDATE',
                'record_id'   => $id,
                'old_values'  => json_encode($oldValues),
                'new_values'  => json_encode($newValues),
                'performed_by'=> Auth::id(),
                'created_at'  => Carbon::now(),
            ]);

            // ── NOTIFIKASI ─────────────────────────────────────────────────────
            // Beritahu semua ADMIN bahwa ada perubahan status
            NotificationHelper::notifyAdminEditIncident(
                (int) $id,
                $oldData->title,
                Auth::user()->name
            );

            // Beritahu USER PEMILIK insiden bahwa statusnya berubah
            if ($oldData->performed_by && $oldData->status !== $validated['status']) {
                NotificationHelper::notifyUserStatusChanged(
                    (int) $oldData->performed_by,
                    (int) $id,
                    $oldData->title,
                    $oldData->status,
                    $validated['status'],
                    Auth::user()->name
                );
            }
            // ──────────────────────────────────────────────────────────────────

            return redirect()->route('incidents.index')->with('success', 'Status insiden berhasil diperbarui!');
        }

        // ── USER / ADMIN: edit konten insiden ─────────────────────────────────
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

        $oldValues = [
            'room_id'        => $oldData->room_id,
            'title'          => $oldData->title,
            'description'    => $oldData->description,
            'severity_level' => $oldData->severity_level,
        ];

        DB::table('incident_logs')->where('id', $id)->update($updateData);

        DB::table('audit_trails')->insert([
            'table_name'  => 'incident_logs',
            'action'      => 'UPDATE',
            'record_id'   => $id,
            'old_values'  => json_encode($oldValues),
            'new_values'  => json_encode($validated),
            'performed_by'=> Auth::id(),
            'created_at'  => Carbon::now(),
        ]);

        // ── NOTIFIKASI ─────────────────────────────────────────────────────────
        // Beritahu semua ADMIN ada insiden yang diedit
        NotificationHelper::notifyAdminEditIncident(
            (int) $id,
            $validated['title'],
            Auth::user()->name
        );

        // Jika yang edit bukan pemilik (admin yg edit milik user), beritahu user pemilik
        if ($oldData->performed_by && $oldData->performed_by !== Auth::id()) {
            NotificationHelper::notifyUserOwnIncidentEdited(
                (int) $oldData->performed_by,
                (int) $id,
                $validated['title'],
                Auth::user()->name
            );
        } else {
            // Pemilik yang edit sendiri — tetap beri konfirmasi ke dirinya sendiri
            NotificationHelper::notifyUserOwnIncidentEdited(
                Auth::id(),
                (int) $id,
                $validated['title'],
                Auth::user()->name
            );
        }
        // ───────────────────────────────────────────────────────────────────────

        return redirect()->route('incidents.index')->with('success', 'Log insiden berhasil diperbarui!');
    }

    // Mekanisme Penghapusan Data Aman (Soft Delete)
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
            'table_name'  => 'incident_logs',
            'action'      => 'SOFT_DELETE',
            'record_id'   => $id,
            'old_values'  => json_encode($oldData),
            'performed_by'=> Auth::id(),
            'created_at'  => Carbon::now(),
        ]);

        // ── NOTIFIKASI ─────────────────────────────────────────────────────────
        // Beritahu semua ADMIN
        NotificationHelper::notifyAdminDeleteIncident(
            (int) $id,
            $oldData->title,
            Auth::user()->name
        );

        // Jika yang hapus bukan pemilik (admin hapus milik user), beritahu user pemilik
        if ($oldData->performed_by && $oldData->performed_by !== Auth::id()) {
            NotificationHelper::notifyUserOwnIncidentDeleted(
                (int) $oldData->performed_by,
                $oldData->title,
                Auth::user()->name
            );
        } else {
            // Pemilik hapus sendiri — konfirmasi ke dirinya
            NotificationHelper::notifyUserOwnIncidentDeleted(
                Auth::id(),
                $oldData->title,
                Auth::user()->name
            );
        }
        // ───────────────────────────────────────────────────────────────────────

        return redirect()->route('incidents.index')->with('success', 'Log insiden berhasil di-soft-delete dengan aman!');
    }

    // Download CSV / Excel
    public function export()
    {
        if (Auth::user()->role === 'admin') {
            $data     = DB::table('incident_logs')->whereNull('deleted_at')->orderBy('created_at', 'desc')->get();
            $filename = 'Semua_Log_Insiden_' . date('Ymd_His') . '.csv';
        } else {
            $data     = DB::table('incident_logs')->whereNull('deleted_at')->where('performed_by', Auth::id())->orderBy('created_at', 'desc')->get();
            $filename = 'Log_Insiden_Saya_' . date('Ymd_His') . '.csv';
        }

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['ID Log', 'ID Ruang', 'Judul Masalah / Kendala', 'Deskripsi Kronologi', 'Tingkat Keparahan', 'Status Kerja', 'Nama Pelapor', 'Waktu Kejadian']);

            foreach ($data as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->room_id ?? 'Ruang 1',
                    $row->title,
                    $row->description ?? 'Tidak ada catatan.',
                    $row->severity_level,
                    $row->status,
                    $row->reported_by_name,
                    $row->created_at ? date('d-m-Y H:i', strtotime($row->created_at)) : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * API untuk Chart Real-time Dashboard Admin
     */
    public function dashboardStats()
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'admin') {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // --- PERUBAHAN LOGIKA PENGHITUNGAN USER ---
            $isHeadAdmin = ($user->id === 1); // Cek apakah ini Head Admin (ID 1)
            
            $countAdmins = DB::table('users')->where('role', 'admin')->count();
            $countUsers  = DB::table('users')->where('role', 'user')->count();

            if ($isHeadAdmin) {
                // Head Admin melihat total semua
                $totalUsers = $countAdmins + $countUsers;
                $userTitle  = "Total Pengguna Sistem";
                $userSubtitle = "{$countAdmins} Admin & {$countUsers} Staf Lapangan";
            } else {
                // Admin biasa hanya melihat staf lapangan
                $totalUsers = $countUsers;
                $userTitle  = "Total Staf Lapangan Aktif";
                $userSubtitle = "Sistem Terkelola";
            }
            // ------------------------------------------

            $totalIncidents = DB::table('incident_logs')->whereNull('deleted_at')->count();

            $severityData = DB::table('incident_logs')
                ->select('severity_level', DB::raw('count(*) as total'))
                ->whereNull('deleted_at')
                ->groupBy('severity_level')
                ->get();

            $roomData = DB::table('incident_logs')
                ->select('room_id', DB::raw('count(*) as total'))
                ->whereNull('deleted_at')
                ->groupBy('room_id')
                ->get();

            $totalAuditTrails = DB::table('audit_trails')->count();

            return response()->json([
                'total_users'     => $totalUsers,
                'user_title'      => $userTitle,       
                'user_subtitle'   => $userSubtitle,    
                'total_incidents' => $totalIncidents,
                'total_audits'    => $totalAuditTrails, // <-- PASTIKAN BARIS INI ADA DI SINI
                'severity_data'   => $severityData,
                'room_data'       => $roomData
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function userDashboard()
    {
        // Ambil 5 data insiden terakhir yang dibuat oleh user ini
        $recentIncidents = DB::table('incident_logs')
            ->where('performed_by', Auth::id())
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('user.dashboard', compact('recentIncidents'));
    }

    
}