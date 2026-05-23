<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Penerima notifikasi (NULL = broadcast ke semua admin)
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // Tipe penerima: 'admin' = semua admin, 'user' = user tertentu via user_id
            $table->string('target_role', 10)->default('admin'); // 'admin' | 'user'

            // Konten notifikasi
            $table->string('type', 50);   // 'incident_new' | 'incident_edit' | 'incident_delete' | 'incident_status' | 'user_add' | 'user_edit' | 'user_delete'
            $table->string('title', 200);
            $table->text('body')->nullable();

            // Relasi ke record sumber (opsional, untuk navigasi)
            $table->string('ref_table', 50)->nullable();  // 'incident_logs' | 'users'
            $table->unsignedBigInteger('ref_id')->nullable();

            // Siapa yang memicu aksi ini
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->string('triggered_by_name', 100)->nullable();

            // Status baca
            $table->timestamp('read_at')->nullable();

            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement('CREATE INDEX idx_notif_user ON notifications (user_id, read_at)');
        DB::statement('CREATE INDEX idx_notif_role ON notifications (target_role, read_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};