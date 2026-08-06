<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel untuk menyimpan riwayat disposisi setiap usulan produk hukum.
     */
    public function up(): void
    {
        Schema::create('submission_dispositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_submission_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_user_id')->nullable()->constrained('users')->onDelete('set null')
                  ->comment('User yang mengirim disposisi (null = sistem/otomatis)');
            $table->foreignId('to_user_id')->nullable()->constrained('users')->onDelete('set null')
                  ->comment('User penerima disposisi');
            $table->string('action')->comment('disposisi|verifikasi|approve|reject|upload_draft|update_status');
            $table->string('from_stage')->nullable()->comment('Stage sebelum aksi dilakukan');
            $table->string('to_stage')->nullable()->comment('Stage setelah aksi dilakukan');
            $table->text('catatan')->nullable()->comment('Catatan atau alasan terkait aksi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_dispositions');
    }
};
