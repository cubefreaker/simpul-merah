<?php

use function Livewire\Volt\{state, mount, layout};
use App\Models\FormSubmission;
use App\Models\SubmissionDisposition;

layout('livewire.layouts.app');

state(['submission' => null]);

mount(function(int $id) {
    $submission = FormSubmission::with(['user', 'dispositions.fromUser'])
        ->findOrFail($id);

    $user = auth()->user();

    if (!$user->isBupati() && !$user->hasDevAccess()) {
        abort(403, 'Halaman ini hanya dapat diakses oleh Bupati.');
    }

    if (!$user->hasDevAccess() && $submission->current_stage !== FormSubmission::STAGE_BUPATI) {
        abort(403, 'Dokumen ini belum sampai pada tahap persetujuan Bupati.');
    }

    $this->submission = $submission;
});

$doApprove = function() {
    $user = auth()->user();
    $submission = $this->submission;

    SubmissionDisposition::create([
        'form_submission_id' => $submission->id,
        'from_user_id'       => $user->id,
        'to_user_id'         => null,
        'action'             => SubmissionDisposition::ACTION_APPROVE,
        'from_stage'         => FormSubmission::STAGE_BUPATI,
        'to_stage'           => FormSubmission::STAGE_SELESAI,
        'catatan'            => 'Disetujui oleh Bupati.',
    ]);

    $submission->update([
        'current_stage' => FormSubmission::STAGE_SELESAI,
        'status'        => 'selesai',
    ]);

    // TODO: Broadcast notifikasi ke semua akun terkait
    session()->flash('message', 'Usulan telah disetujui! Notifikasi dikirim ke semua pihak terkait.');
    $this->redirect(route('form-submissions.show', $submission->id), navigate: true);
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
                    <h2 class="text-xl font-bold text-gray-900">Persetujuan Bupati</h2>
                    <p class="text-sm text-gray-500">Tahap akhir persetujuan usulan produk hukum daerah</p>
                </div>
            </div>

            {{-- Info Usulan --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">{{ $submission->judul_rphd }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $submission->jenis }}</p>
                    </div>
                    <span class="font-mono font-bold text-red-700 bg-red-50 px-3 py-1 rounded-lg text-sm">
                        {{ $submission->nomor_pengajuan ?? '—' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Pengusul</p>
                        <p class="font-medium text-gray-900">{{ $submission->user->name }}</p>
                        <p class="text-sm text-gray-500">{{ $submission->user->opd_name ?? $submission->pemerintah_daerah }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Tanggal Permohonan</p>
                        <p class="font-medium text-gray-900">{{ $submission->tanggal_permohonan->translatedFormat('d F Y') }}</p>
                    </div>
                </div>

                {{-- Dokumen --}}
                <h4 class="font-semibold text-gray-700 text-xs uppercase tracking-wider mb-3">Dokumen</h4>
                <div class="space-y-2">
                    @foreach([
                        'file_1' => 'Surat Pengantar',
                        'file_draft_produk_hukum' => 'Draft Produk Hukum (hasil kajian)',
                        'file_npkmd' => 'NPKMD (Nota Pengajuan Kajian dan Harmonisasi)',
                    ] as $field => $label)
                        @if($submission->$field)
                        @php
                            $ext = pathinfo($submission->$field, PATHINFO_EXTENSION);
                            $safeNomor = str_replace('/', '-', $submission->nomor_pengajuan ?? 'Draft');
                            $safeLabel = str_replace(['(', ')'], '', $label);
                            $downloadName = $safeLabel . ' - ' . $safeNomor . '.' . $ext;
                        @endphp
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                            <div class="p-2 bg-indigo-100 rounded-lg">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700 flex-1">{{ $label }}</span>
                            <a href="{{ route('file.download', ['path' => $submission->$field, 'name' => $downloadName]) }}" target="_blank"
                               class="text-xs text-primary hover:underline font-medium">Download</a>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Persetujuan --}}
            <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-2xl border border-amber-200 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-amber-100 rounded-xl">
                        <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-amber-800">Keputusan Persetujuan</h3>
                </div>
                <p class="text-sm text-amber-700 mb-6">
                    Dengan menekan tombol di bawah, Anda menyatakan menyetujui usulan produk hukum ini. 
                    Persetujuan akan dicatat dalam sistem dan semua pihak terkait akan mendapat notifikasi.
                </p>
                <button wire:click="doApprove"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed"
                        wire:confirm="Apakah Anda yakin ingin menyetujui usulan produk hukum ini?"
                        class="w-full py-3 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow-lg shadow-amber-500/30 transition-all text-sm flex items-center justify-center gap-2">
                    <span wire:loading.remove>
                        <svg class="w-5 h-5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Setujui Usulan Produk Hukum Ini
                    </span>
                    <span wire:loading class="flex items-center gap-2">
                        <div class="loading-spinner loading-spinner-sm"></div>
                        Memproses Persetujuan...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
