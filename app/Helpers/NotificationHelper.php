<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * NotificationHelper
 *
 * Kelas pembantu untuk menciptakan notifikasi secara terpusat.
 * Dipanggil dari Controller manapun tanpa duplikasi logika.
 */
class NotificationHelper
{
    // =========================================================================
    // NOTIFIKASI UNTUK ADMIN (broadcast — semua akun admin menerima)
    // =========================================================================

    /**
     * Notifikasi ke SEMUA ADMIN: ada laporan insiden baru dari user/admin.
     */
    public static function notifyAdminNewIncident(int $incidentId, string $incidentTitle, string $roomId, string $severity, string $reporterName): void
    {
        $severityLabel = match($severity) {
            'Critical' => '🔴 CRITICAL',
            'Warning'  => '⚠️ Warning',
            default    => '🔵 Normal',
        };

        self::insertForAllAdmins(
            type:              'incident_new',
            title:             "Laporan Insiden Baru [{$severityLabel}]",
            body:              "{$reporterName} melaporkan: \"{$incidentTitle}\" di {$roomId}.",
            refTable:          'incident_logs',
            refId:             $incidentId,
            triggeredBy:       Auth::id(),
            triggeredByName:   $reporterName,
        );
    }

    /**
     * Notifikasi ke SEMUA ADMIN: insiden diedit oleh user/admin.
     */
    public static function notifyAdminEditIncident(int $incidentId, string $incidentTitle, string $editorName): void
    {
        self::insertForAllAdmins(
            type:              'incident_edit',
            title:             'Log Insiden Diperbarui',
            body:              "{$editorName} mengedit log: \"{$incidentTitle}\".",
            refTable:          'incident_logs',
            refId:             $incidentId,
            triggeredBy:       Auth::id(),
            triggeredByName:   $editorName,
        );
    }

    /**
     * Notifikasi ke SEMUA ADMIN: insiden dihapus (soft-delete).
     */
    public static function notifyAdminDeleteIncident(int $incidentId, string $incidentTitle, string $deleterName): void
    {
        self::insertForAllAdmins(
            type:              'incident_delete',
            title:             'Log Insiden Dihapus',
            body:              "{$deleterName} menghapus log: \"{$incidentTitle}\".",
            refTable:          'incident_logs',
            refId:             $incidentId,
            triggeredBy:       Auth::id(),
            triggeredByName:   $deleterName,
        );
    }

    /**
     * Notifikasi ke SEMUA ADMIN: pengguna baru ditambahkan.
     */
    public static function notifyAdminUserAdded(int $newUserId, string $newUserName, string $newUserRole, string $actorName): void
    {
        self::insertForAllAdmins(
            type:              'user_add',
            title:             'Pengguna Baru Ditambahkan',
            body:              "{$actorName} menambahkan akun \"{$newUserName}\" dengan peran {$newUserRole}.",
            refTable:          'users',
            refId:             $newUserId,
            triggeredBy:       Auth::id(),
            triggeredByName:   $actorName,
        );
    }

    /**
     * Notifikasi ke SEMUA ADMIN: data pengguna diperbarui.
     */
    public static function notifyAdminUserEdited(int $targetUserId, string $targetUserName, string $actorName): void
    {
        self::insertForAllAdmins(
            type:              'user_edit',
            title:             'Data Pengguna Diperbarui',
            body:              "{$actorName} memperbarui akun \"{$targetUserName}\".",
            refTable:          'users',
            refId:             $targetUserId,
            triggeredBy:       Auth::id(),
            triggeredByName:   $actorName,
        );
    }

    /**
     * Notifikasi ke SEMUA ADMIN: akun pengguna dihapus.
     */
    public static function notifyAdminUserDeleted(string $deletedUserName, string $actorName): void
    {
        self::insertForAllAdmins(
            type:              'user_delete',
            title:             'Akun Pengguna Dihapus',
            body:              "{$actorName} menghapus akun \"{$deletedUserName}\" dari sistem.",
            refTable:          'users',
            refId:             null,
            triggeredBy:       Auth::id(),
            triggeredByName:   $actorName,
        );
    }

    // =========================================================================
    // NOTIFIKASI UNTUK USER TERTENTU (personal — hanya pemilik data)
    // =========================================================================

    /**
     * Notifikasi ke USER PEMILIK: laporan insiden mereka sendiri berhasil dikirim.
     */
    public static function notifyUserOwnIncidentCreated(int $userId, int $incidentId, string $incidentTitle, string $roomId, string $severity): void
    {
        $severityLabel = match($severity) {
            'Critical' => '🔴 CRITICAL',
            'Warning'  => '⚠️ Warning',
            default    => '🔵 Normal',
        };

        self::insertForUser(
            userId:            $userId,
            type:              'incident_new',
            title:             "Laporan Anda Berhasil Dikirim [{$severityLabel}]",
            body:              "Laporan \"{$incidentTitle}\" di {$roomId} telah masuk ke database sistem.",
            refTable:          'incident_logs',
            refId:             $incidentId,
            triggeredBy:       $userId,
            triggeredByName:   Auth::user()->name,
        );
    }

    /**
     * Notifikasi ke USER PEMILIK: laporan insiden mereka diedit.
     */
    public static function notifyUserOwnIncidentEdited(int $userId, int $incidentId, string $incidentTitle, string $editorName): void
    {
        self::insertForUser(
            userId:            $userId,
            type:              'incident_edit',
            title:             'Log Insiden Anda Diperbarui',
            body:              "{$editorName} telah mengedit laporan \"{$incidentTitle}\".",
            refTable:          'incident_logs',
            refId:             $incidentId,
            triggeredBy:       Auth::id(),
            triggeredByName:   $editorName,
        );
    }

    /**
     * Notifikasi ke USER PEMILIK: laporan insiden mereka dihapus.
     */
    public static function notifyUserOwnIncidentDeleted(int $userId, string $incidentTitle, string $deleterName): void
    {
        self::insertForUser(
            userId:            $userId,
            type:              'incident_delete',
            title:             'Log Insiden Anda Dihapus',
            body:              "{$deleterName} menghapus laporan \"{$incidentTitle}\" dari sistem.",
            refTable:          'incident_logs',
            refId:             null,
            triggeredBy:       Auth::id(),
            triggeredByName:   $deleterName,
        );
    }

    /**
     * Notifikasi ke USER PEMILIK: status kerja insiden mereka diubah oleh admin.
     */
    public static function notifyUserStatusChanged(int $userId, int $incidentId, string $incidentTitle, string $oldStatus, string $newStatus, string $adminName): void
    {
        $statusLabel = match($newStatus) {
            'In Progress' => '🔄 In Progress',
            'Resolved'    => '✅ Resolved',
            default       => '📋 Open',
        };

        self::insertForUser(
            userId:            $userId,
            type:              'incident_status',
            title:             "Status Insiden Diperbarui → {$statusLabel}",
            body:              "Admin {$adminName} mengubah status \"{$incidentTitle}\" dari {$oldStatus} menjadi {$newStatus}.",
            refTable:          'incident_logs',
            refId:             $incidentId,
            triggeredBy:       Auth::id(),
            triggeredByName:   $adminName,
        );
    }

    // =========================================================================
    // INTERNAL WRITER
    // =========================================================================

    /**
     * Kirim notifikasi ke SEMUA pengguna yang berperan sebagai 'admin'.
     * Setiap admin mendapat row tersendiri agar bisa dibaca/ditandai per-orang.
     */
    private static function insertForAllAdmins(
        string  $type,
        string  $title,
        ?string $body,
        ?string $refTable,
        ?int    $refId,
        ?int    $triggeredBy,
        ?string $triggeredByName,
    ): void {
        $adminIds = DB::table('users')->where('role', 'admin')->pluck('id');

        $rows = $adminIds->map(fn ($id) => [
            'user_id'           => $id,
            'target_role'       => 'admin',
            'type'              => $type,
            'title'             => $title,
            'body'              => $body,
            'ref_table'         => $refTable,
            'ref_id'            => $refId,
            'triggered_by'      => $triggeredBy,
            'triggered_by_name' => $triggeredByName,
            'read_at'           => null,
            'created_at'        => Carbon::now(),
        ])->toArray();

        if (!empty($rows)) {
            DB::table('notifications')->insert($rows);
        }
    }

    /**
     * Kirim notifikasi ke SATU user spesifik berdasarkan user_id.
     */
    private static function insertForUser(
        int     $userId,
        string  $type,
        string  $title,
        ?string $body,
        ?string $refTable,
        ?int    $refId,
        ?int    $triggeredBy,
        ?string $triggeredByName,
    ): void {
        DB::table('notifications')->insert([
            'user_id'           => $userId,
            'target_role'       => 'user',
            'type'              => $type,
            'title'             => $title,
            'body'              => $body,
            'ref_table'         => $refTable,
            'ref_id'            => $refId,
            'triggered_by'      => $triggeredBy,
            'triggered_by_name' => $triggeredByName,
            'read_at'           => null,
            'created_at'        => Carbon::now(),
        ]);
    }
}