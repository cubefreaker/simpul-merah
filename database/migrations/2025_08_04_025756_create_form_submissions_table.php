<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('judul_rphd', 255);
            $table->string('nomor_permohonan', 255);
            $table->date('tanggal_permohonan');
            $table->string('perihal_permohonan', 255);
            $table->string('pemerintah_daerah', 100);
            $table->string('jenis', 255);
            $table->string('file_1')->nullable()->comment('Surat Pengantar');
            $table->string('file_2')->nullable()->comment('Draft Produk Hukum');
            $table->string('file_3')->nullable()->comment('Lampiran');
            $table->string('file_4')->nullable()->comment('Kelengkapan Lainnya');
            $table->string('file_5')->nullable()->comment('');
            $table->enum('status', ['belum diproses', 'diproses', 'ditolak', 'selesai'])->default('belum diproses');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
