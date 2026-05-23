<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AuditTrailController extends Controller
{
    public function index()
    {
        // Ambil data user aktif untuk kebutuhan layout master (navigasi atas)
        $user = Auth::user();

        // Mengambil data audit trails dan di-join ke tabel users
        $trails = DB::table('audit_trails')
            ->leftJoin('users', 'audit_trails.performed_by', '=', 'users.id')
            ->select('audit_trails.*', 'users.name as user_name')
            ->orderBy('audit_trails.created_at', 'desc')
            ->paginate(20);

        return view('admin.audit_trails', compact('trails', 'user'));
    }

    // FUNGSI BARU UNTUK EXPORT EXCEL/CSV DATA AUDIT
    public function export()
    {
        $data = DB::table('audit_trails')
            ->leftJoin('users', 'audit_trails.performed_by', '=', 'users.id')
            ->select('audit_trails.*', 'users.name as user_name')
            ->orderBy('audit_trails.created_at', 'desc')
            ->get();

        $filename = 'Riwayat_Audit_Sistem_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            // Menambahkan BOM agar Excel membaca karakter UTF-8 / Tulisan tanda baca dengan rapi
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Baris Header Kolom
            fputcsv($file, ['ID Log', 'Waktu Kejadian', 'Nama Pengguna / Pelaku', 'Jenis Aksi', 'Nama Target Tabel', 'ID Record', 'Data Lama (Old Values)', 'Data Baru (New Values)']);

            // Baris Isi Data
            foreach ($data as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->created_at ? date('d-m-Y H:i:s', strtotime($row->created_at)) : '-',
                    $row->user_name ?? 'Sistem',
                    $row->action,
                    $row->table_name,
                    $row->record_id,
                    $row->old_values ?? 'null',
                    $row->new_values ?? 'null',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}