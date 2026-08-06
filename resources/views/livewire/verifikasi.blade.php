<?php

use function Livewire\Volt\{state, mount, layout};
use App\Models\FormSubmission;
use App\Models\SubmissionDisposition;
use App\Models\User;
use Livewire\WithFileUploads;

layout('livewire.layouts.app');

state([
    'submission'    => null,
    'catatan'       => '',
    'showRejectModal' => false,
    'rejectionNote' => '',
    'uploadedDraft' => null,
]);

mount(function(int $id) {
    $submission = FormSubmission::with(['user', 'dispositions.fromUser', 'dispositions.toUser'])
        ->findOrFail($id);

    $user = auth()->user();

    if (!$user->isVerifikator() && !$user->isStaf() && !$user->hasDevAccess()) {
        abort(403, 'Anda tidak berwenang mengakses halaman ini.');
    }

    $this->submission = $submission;
});

$doVerifikasi = function() {
    $user = auth()->user();
    $submission = $this->submission;

    if (!$user->hasDevAccess() && !$submission->canBeActedBy($user)) {
        session()->flash('error', 'Bukan giliran Anda untuk melakukan verifikasi.');
        return;
    }

    // Tentukan stage berikutnya
    $nextStage = match($submission->current_stage) {
        FormSubmission::STAGE_VERIFIKASI_KABAG   => FormSubmission::STAGE_VERIFIKASI_ASISTEN,
        FormSubmission::STAGE_VERIFIKASI_ASISTEN => FormSubmission::STAGE_VERIFIKASI_SEKDA,
        FormSubmission::STAGE_VERIFIKASI_SEKDA   => FormSubmission::STAGE_BUPATI,
        default => null,
    };

    if (!$nextStage) {
        session()->flash('error', 'Tidak dapat memverifikasi dari stage ini.');
        return;
    }

    // Generate NPKMD jika U5 (SEKDA) verifikasi
    $npkmdPath = $submission->file_npkmd;
    if ($user->isSekda() && $submission->current_stage === FormSubmission::STAGE_VERIFIKASI_SEKDA) {
        // TODO: Generate template NPKMD dari data submission
        // Untuk saat ini catat bahwa NPKMD perlu diproses manual
        $npkmdPath = 'npkmd_generated_' . $submission->id . '_' . now()->format('Ymd') . '.docx';
    }

    SubmissionDisposition::create([
        'form_submission_id' => $submission->id,
        'from_user_id'       => $user->id,
        'to_user_id'         => null, // akan di-set by system
        'action'             => SubmissionDisposition::ACTION_VERIFIKASI,
        'from_stage'         => $submission->current_stage,
        'to_stage'           => $nextStage,
        'catatan'            => $this->catatan,
    ]);

    $submission->update([
        'current_stage' => $nextStage,
        'status'        => $nextStage === FormSubmission::STAGE_SELESAI ? 'selesai' : 'diproses',
        'file_npkmd'    => $npkmdPath,
    ]);

    $this->catatan = '';
    session()->flash('message', 'Verifikasi berhasil! Dokumen diteruskan ke tahap berikutnya.');
    $this->redirect(route('form-submissions.show', $submission->id), navigate: true);
};

$openRejectModal = function() {
    $this->showRejectModal = true;
    $this->rejectionNote = '';
};

$closeRejectModal = function() {
    $this->showRejectModal = false;
    $this->rejectionNote = '';
};

$doReject = function() {
    if (empty(trim($this->rejectionNote))) {
        $this->addError('rejectionNote', 'Alasan penolakan wajib diisi.');
        return;
    }

    $user = auth()->user();
    $submission = $this->submission;

    SubmissionDisposition::create([
        'form_submission_id' => $submission->id,
        'from_user_id'       => $user->id,
        'to_user_id'         => $submission->user_id, // kembali ke pengusul
        'action'             => SubmissionDisposition::ACTION_REJECT,
        'from_stage'         => $submission->current_stage,
        'to_stage'           => FormSubmission::STAGE_DITOLAK,
        'catatan'            => $this->rejectionNote,
    ]);

    $submission->update([
        'current_stage'    => FormSubmission::STAGE_DITOLAK,
        'status'           => 'ditolak',
        'rejected_at_stage' => $submission->current_stage,
        'rejection_note'   => $this->rejectionNote,
    ]);

    $this->showRejectModal = false;
    session()->flash('message', 'Dokumen telah ditolak. Pengusul akan menerima notifikasi.');
    $this->redirect(route('form-submissions.index'), navigate: true);
};

?>

<div>
    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Header --}}
            <div class="flex items-center gap-4">
                <a href="{{ route('form-submissions.show', $submission->id) }}" wire:navigate
                   class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Verifikasi Dokumen</h2>
                    <p class="text-sm text-gray-500">Periksa dan verifikasi dokumen usulan produk hukum</p>
                </div>
            </div>

            @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                {{ session('error') }}
            </div>
            @endif

            {{-- Info & Dokumen --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wider mb-4">Detail Usulan</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-xs text-gray-400">Nomor Pengajuan</p>
                        <p class="font-mono font-bold text-red-700">{{ $submission->nomor_pengajuan ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Tahap Saat Ini</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $submission->current_stage_color }}">
                            {{ $submission->current_stage_label }}
                        </span>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-gray-400">Judul RPHD</p>
                        <p class="font-medium text-gray-900">{{ $submission->judul_rphd }}</p>
                    </div>
                </div>

                {{-- Dokumen --}}
                <h4 class="font-semibold text-gray-700 text-xs uppercase tracking-wider mb-3">Dokumen untuk Diperiksa</h4>
                <div class="space-y-2">
                    @if($submission->file_1)
                    @php
                        $ext = pathinfo($submission->file_1, PATHINFO_EXTENSION);
                        $safeNomor = str_replace('/', '-', $submission->nomor_pengajuan ?? 'Draft');
                        $downloadName = 'Surat Pengantar - ' . $safeNomor . '.' . $ext;
                    @endphp
                    <a href="{{ route('file.download', ['path' => $submission->file_1, 'name' => $downloadName]) }}" target="_blank"
                       class="flex items-center gap-3 p-3 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors group">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-primary">Surat Pengantar</span>
                        <svg class="w-4 h-4 text-gray-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </a>
                    @endif
                    @if($submission->file_draft_produk_hukum)
                    @php
                        $ext = pathinfo($submission->file_draft_produk_hukum, PATHINFO_EXTENSION);
                        $safeNomor = str_replace('/', '-', $submission->nomor_pengajuan ?? 'Draft');
                        $downloadName = 'Draft Produk Hukum - ' . $safeNomor . '.' . $ext;
                    @endphp
                    <a href="{{ route('file.download', ['path' => $submission->file_draft_produk_hukum, 'name' => $downloadName]) }}" target="_blank"
                       class="flex items-center gap-3 p-3 bg-orange-50 hover:bg-orange-100 rounded-xl transition-colors group">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-orange-700 group-hover:text-orange-800">Draft Produk Hukum (hasil kajian staf)</span>
                        <svg class="w-4 h-4 text-gray-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </a>
                    @endif
                </div>
            </div>

            {{-- Catatan & Aksi --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wider mb-4">Keputusan Verifikasi</h3>

                @if(auth()->user()->isSekda())
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                    <p class="text-sm text-amber-800">
                        <strong>Catatan:</strong> Setelah Anda klik "Verifikasi", sistem akan otomatis menyiapkan template NPKMD berdasarkan data usulan ini, kemudian dokumen akan diteruskan ke Bupati untuk persetujuan.
                    </p>
                </div>
                @endif

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Catatan Verifikasi <span class="text-gray-400">(opsional)</span>
                    </label>
                    <textarea wire:model="catatan" rows="3"
                              placeholder="Catatan atau komentar terkait verifikasi..."
                              class="block w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all resize-none"></textarea>
                </div>

                <div class="flex justify-between items-center">
                    {{-- Tombol Tolak --}}
                    <button wire:click="openRejectModal"
                            class="px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-700 text-sm font-semibold rounded-xl border border-red-200 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Tolak Dokumen
                    </button>

                    {{-- Tombol Verifikasi --}}
                    <button wire:click="doVerifikasi"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-not-allowed"
                            class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-emerald-500/20 transition-all flex items-center gap-2">
                        <span wire:loading.remove>
                            <svg class="w-4 h-4 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Verifikasi & Teruskan
                        </span>
                        <span wire:loading class="flex items-center gap-2">
                            <div class="loading-spinner loading-spinner-sm"></div>
                            Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Penolakan --}}
    @if($showRejectModal)
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 w-full max-w-md mx-4 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-red-100 rounded-xl">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.834-1.964-.834-2.732 0L3.072 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Tolak Dokumen</h3>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Dokumen akan dikembalikan ke pengusul. Pengusul akan menerima notifikasi beserta alasan penolakan.
            </p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Alasan Penolakan <span class="text-red-500">*</span>
                </label>
                <textarea wire:model="rejectionNote" rows="4"
                          placeholder="Tuliskan alasan penolakan secara jelas..."
                          class="block w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400 transition-all resize-none"></textarea>
                @error('rejectionNote')
                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                @enderror
            </div>
            <div class="flex justify-end gap-3">
                <button wire:click="closeRejectModal"
                        class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-all">
                    Batal
                </button>
                <button wire:click="doReject"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75"
                        class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-all">
                    <span wire:loading.remove>Konfirmasi Tolak</span>
                    <span wire:loading>Memproses...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
