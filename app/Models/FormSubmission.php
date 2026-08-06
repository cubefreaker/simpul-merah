<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nomor_pengajuan',
        'judul_rphd',
        'nomor_permohonan',
        'tanggal_permohonan',
        'perihal_permohonan',
        'pemerintah_daerah',
        'jenis',
        'file_1',
        'file_2',
        'file_3',
        'file_4',
        'file_5',
        'status',
        'current_stage',
        'assigned_to',
        'file_draft_produk_hukum',
        'file_npkmd',
        'tanggal_diundangkan',
        'rejected_at_stage',
        'rejection_note',
    ];

    protected $casts = [
        'tanggal_permohonan' => 'date',
    ];

    // ============================================================
    // STAGE CONSTANTS — Alur Workflow SIMPUL MERAH
    // ============================================================

    const STAGE_PENGUSUL          = 'pengusul';           // Entry point — U1 baru submit (segera pindah ke sekda)
    const STAGE_SEKDA             = 'sekda';              // Menunggu aksi U5
    const STAGE_ASISTEN           = 'asisten';            // U5 disposisi ke U4
    const STAGE_KABAG_HUKUM       = 'kabag_hukum';        // U4 disposisi ke U2
    const STAGE_STAF              = 'staf';               // U2 disposisi ke U3
    const STAGE_VERIFIKASI_KABAG  = 'verifikasi_kabag';   // U3 selesai, kirim ke U2
    const STAGE_VERIFIKASI_ASISTEN = 'verifikasi_asisten'; // U2 verif, kirim ke U4
    const STAGE_VERIFIKASI_SEKDA  = 'verifikasi_sekda';   // U4 verif, kirim ke U5
    const STAGE_BUPATI            = 'bupati';             // U5 verif+NPKMD, kirim ke U6
    const STAGE_SELESAI           = 'selesai';            // U6 approve
    const STAGE_DIUNDANGKAN       = 'diundangkan';        // U3 tandai setelah Bupati menetapkan
    const STAGE_DITOLAK           = 'ditolak';            // Ditolak di salah satu tahap

    /**
     * Pilihan instruksi disposisi (sesuai gambar PRD)
     */
    const INSTRUKSI_DISPOSISI = [
        'Masukkan Agenda',
        'Catat',
        'Laporkan Hasilnya',
        'Pedomani',
        'Arsipkan',
        'Tolong Disiapkan',
        'Koordinasikan',
        'Hadiri',
        'Perbaiki',
        'Selesaikan',
        'Tindak Lanjuti',
    ];

    /**
     * Stage labels for UI display
     */
    public static function getStageLabelMap(): array
    {
        return [
            self::STAGE_PENGUSUL           => 'Baru Diajukan',
            self::STAGE_SEKDA              => 'Di SEKDA',
            self::STAGE_ASISTEN            => 'Di Asisten I',
            self::STAGE_KABAG_HUKUM        => 'Di Kabag Hukum',
            self::STAGE_STAF               => 'Dikaji Staf',
            self::STAGE_VERIFIKASI_KABAG   => 'Verifikasi Kabag Hukum',
            self::STAGE_VERIFIKASI_ASISTEN => 'Verifikasi Asisten I',
            self::STAGE_VERIFIKASI_SEKDA   => 'Verifikasi SEKDA',
            self::STAGE_BUPATI             => 'Menunggu Persetujuan Bupati',
            self::STAGE_SELESAI            => 'Disetujui Bupati',
            self::STAGE_DIUNDANGKAN        => 'Telah Diundangkan',
            self::STAGE_DITOLAK            => 'Ditolak',
        ];
    }

    /**
     * Badge colors per stage untuk UI
     */
    public static function getStageColorMap(): array
    {
        return [
            self::STAGE_PENGUSUL           => 'bg-gray-100 text-gray-700 border-gray-200',
            self::STAGE_SEKDA              => 'bg-blue-100 text-blue-800 border-blue-200',
            self::STAGE_ASISTEN            => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            self::STAGE_KABAG_HUKUM        => 'bg-purple-100 text-purple-800 border-purple-200',
            self::STAGE_STAF               => 'bg-orange-100 text-orange-800 border-orange-200',
            self::STAGE_VERIFIKASI_KABAG   => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            self::STAGE_VERIFIKASI_ASISTEN => 'bg-lime-100 text-lime-800 border-lime-200',
            self::STAGE_VERIFIKASI_SEKDA   => 'bg-cyan-100 text-cyan-800 border-cyan-200',
            self::STAGE_BUPATI             => 'bg-amber-100 text-amber-800 border-amber-200',
            self::STAGE_SELESAI            => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::STAGE_DIUNDANGKAN        => 'bg-teal-100 text-teal-800 border-teal-200',
            self::STAGE_DITOLAK            => 'bg-red-100 text-red-800 border-red-200',
        ];
    }

    /**
     * Dapatkan label stage yang mudah dibaca
     */
    public function getCurrentStageLabelAttribute(): string
    {
        return self::getStageLabelMap()[$this->current_stage] ?? ucfirst($this->current_stage);
    }

    /**
     * Dapatkan warna badge stage
     */
    public function getCurrentStageColorAttribute(): string
    {
        return self::getStageColorMap()[$this->current_stage] ?? 'bg-gray-100 text-gray-700';
    }

    /**
     * Cek apakah user tertentu bisa melakukan aksi pada submission ini.
     * Berdasarkan stage aktif dan role user.
     */
    public function canBeActedBy(User $user): bool
    {
        if ($user->hasDevAccess()) return true;

        return match($this->current_stage) {
            self::STAGE_SEKDA              => $user->isSekda(),
            self::STAGE_ASISTEN            => $user->isAsisten(),
            self::STAGE_KABAG_HUKUM        => $user->isKabagHukum(),
            self::STAGE_STAF               => $user->isStaf(),
            self::STAGE_VERIFIKASI_KABAG   => $user->isKabagHukum(),
            self::STAGE_VERIFIKASI_ASISTEN => $user->isAsisten(),
            self::STAGE_VERIFIKASI_SEKDA   => $user->isSekda(),
            self::STAGE_BUPATI             => $user->isBupati(),
            self::STAGE_SELESAI            => $user->isStaf(), // U3 bisa tandai diundangkan
            default                        => false,
        };
    }

    /**
     * Cek apakah user bisa melihat submission ini (read access)
     */
    public function canBeViewedBy(User $user): bool
    {
        if ($user->hasDevAccess()) return true;
        if ($user->isSkpd()) return $this->user_id === $user->id;
        if ($user->canMonitorAll()) return true;
        if ($user->isStaf()) return $this->assigned_to === $user->id;
        return false;
    }

    // ============================================================
    // RELATIONS
    // ============================================================

    /**
     * Get the user that owns the form submission (U1 - SKPD)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user currently assigned to handle this submission
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the full disposition history for this submission
     */
    public function dispositions()
    {
        return $this->hasMany(SubmissionDisposition::class)->orderBy('created_at', 'asc');
    }

    // ============================================================
    // STATIC HELPERS
    // ============================================================

    /**
     * Get the status options
     */
    public static function getStatusOptions(): array
    {
        return [
            'belum diproses' => 'Belum Diproses',
            'diproses'       => 'Diproses',
            'ditolak'        => 'Ditolak',
            'selesai'        => 'Selesai',
        ];
    }

    /**
     * Get the jenis options
     */
    public static function getJenisOptions(): array
    {
        return [
            'Rancangan Peraturan Daerah'   => 'Rancangan Peraturan Daerah',
            'Rancangan Peraturan Bupati'   => 'Rancangan Peraturan Bupati',
            'Surat Keputusan'              => 'Surat Keputusan',
        ];
    }

    /**
     * Generate auto-incrementing nomor_pengajuan based on jenis document.
     */
    public static function generateNomorPengajuan(string $jenis): string
    {
        $year = date('Y');

        $prefix = match($jenis) {
            'Surat Keputusan'            => 'SK',
            'Rancangan Peraturan Bupati' => 'PB',
            default                      => 'PHD',
        };

        $latestDoc = self::where('nomor_pengajuan', 'like', "{$prefix}/%/{$year}")
            ->orderBy('id', 'desc')
            ->first();

        if (!$latestDoc) {
            $number = 1;
        } else {
            // Extract the number part from "SK/001/2026"
            $parts = explode('/', $latestDoc->nomor_pengajuan);
            $number = intval($parts[1]) + 1;
        }

        return sprintf("%s/%03d/%s", $prefix, $number, $year);
    }
}
