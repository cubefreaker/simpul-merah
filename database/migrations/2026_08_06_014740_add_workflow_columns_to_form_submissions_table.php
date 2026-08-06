<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan kolom workflow ke tabel form_submissions untuk mendukung
     * alur disposisi berjenjang sesuai PRD SIMPUL MERAH.
     */
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            // Stage saat ini dalam alur workflow
            $table->string('current_stage')->default('pengusul')
                  ->after('status')
                  ->comment('pengusul|sekda|asisten|kabag_hukum|staf|verifikasi_kabag|verifikasi_asisten|verifikasi_sekda|bupati|selesai|ditolak');

            // User yang sedang memegang/memproses dokumen
            $table->foreignId('assigned_to')->nullable()->after('current_stage')
                  ->constrained('users')->onDelete('set null')
                  ->comment('User yang sedang memegang dokumen (biasanya U3 saat pengerjaan)');

            // File hasil pekerjaan staf (U3)
            $table->string('file_draft_produk_hukum')->nullable()->after('file_5')
                  ->comment('File draft produk hukum hasil kerja U3 (upload setelah kajian)');

            // File NPKMD yang di-generate otomatis saat U5 verifikasi
            $table->string('file_npkmd')->nullable()->after('file_draft_produk_hukum')
                  ->comment('Template NPKMD yang di-generate otomatis saat SEKDA (U5) verifikasi');

            // Audit trail penolakan
            $table->string('rejected_at_stage')->nullable()->after('file_npkmd')
                  ->comment('Nama stage saat dokumen ditolak, untuk keperluan audit');
            $table->text('rejection_note')->nullable()->after('rejected_at_stage')
                  ->comment('Alasan penolakan — wajib diisi saat reject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn([
                'current_stage',
                'assigned_to',
                'file_draft_produk_hukum',
                'file_npkmd',
                'rejected_at_stage',
                'rejection_note',
            ]);
        });
    }
};
