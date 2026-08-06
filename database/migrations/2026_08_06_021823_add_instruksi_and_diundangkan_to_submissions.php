<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom instruksi ke submission_dispositions
        Schema::table('submission_dispositions', function (Blueprint $table) {
            $table->json('instruksi')->nullable()
                  ->after('catatan')
                  ->comment('Array instruksi disposisi yang dipilih dari checkbox (wajib saat disposisi)');
        });

        // 2. Tambah kolom tanggal_diundangkan ke form_submissions
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->date('tanggal_diundangkan')->nullable()
                  ->after('file_npkmd')
                  ->comment('Tanggal produk hukum diundangkan, diisi oleh U3 setelah Bupati menetapkan');
        });
    }

    public function down(): void
    {
        Schema::table('submission_dispositions', function (Blueprint $table) {
            $table->dropColumn('instruksi');
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn('tanggal_diundangkan');
        });
    }
};
