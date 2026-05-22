<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Incident Logs (Sudah Mendukung room_id, reported_by_name, dan status alur kerja)
        Schema::create('incident_logs', function (Blueprint $table) {
            $table->id();
            $table->string('room_id', 50)->nullable(); // Tambahan Lapangan: Ruang 1 - Ruang 5
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('severity_level', 20); // 'Normal', 'Warning', 'Critical'
            $table->string('status', 20)->default('Open'); // 'Open', 'In Progress', 'Resolved'
            $table->string('reported_by_name', 100)->nullable(); // Menangkap Otomatis Nama Akun yang Login
            $table->unsignedBigInteger('reported_by')->nullable(); // ID User Relasional (Opsional)
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable(); // Soft-delete mechanism
        });

        // 2. Tabel Audit Trails
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 50);
            $table->string('action', 15); // 'INSERT', 'UPDATE', 'SOFT_DELETE'
            $table->unsignedBigInteger('record_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 3. Optimasi Database dengan Standard Indexing (Kompatibel dengan MariaDB/MySQL XAMPP)
        DB::statement('CREATE INDEX idx_incidents_priority ON incident_logs (severity_level, status)');
        DB::statement('CREATE INDEX idx_incidents_room ON incident_logs (room_id)');
        DB::statement('CREATE INDEX idx_incidents_created ON incident_logs (created_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
        Schema::dropIfExists('incident_logs');
    }
};