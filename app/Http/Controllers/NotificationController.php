<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * GET /notifications/fetch
     * Mengambil daftar notifikasi milik user yang sedang login.
     * Mengembalikan JSON: { unread_count, notifications[] }
     */
    public function fetch(Request $request)
    {
        $userId = Auth::id();
        $limit  = (int) $request->query('limit', 20);

        $notifications = DB::table('notifications')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($n) {
                return [
                    'id'                => $n->id,
                    'type'              => $n->type,
                    'title'             => $n->title,
                    'body'              => $n->body,
                    'ref_table'         => $n->ref_table,
                    'ref_id'            => $n->ref_id,
                    'triggered_by_name' => $n->triggered_by_name,
                    'is_read'           => !is_null($n->read_at),
                    'created_at'        => $n->created_at,
                    'time_ago'          => Carbon::parse($n->created_at)->diffForHumans(),
                ];
            });

        $unreadCount = DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unread_count'  => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * POST /notifications/read/{id}
     * Tandai SATU notifikasi sebagai sudah dibaca.
     */
    public function markRead(int $id)
    {
        DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return response()->json(['ok' => true]);
    }

    /**
     * POST /notifications/read-all
     * Tandai SEMUA notifikasi milik user ini sebagai sudah dibaca.
     */
    public function markAllRead()
    {
        DB::table('notifications')
            ->where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return response()->json(['ok' => true]);
    }

    public function index()
    {
        // Memanggil file resources/views/layout/notifications.blade.php
        return view('layout.notifications');
    }
}