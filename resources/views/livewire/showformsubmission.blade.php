<?php

use function Livewire\Volt\{state, mount, layout, uses};
use App\Models\FormSubmission;
use App\Models\SubmissionDisposition;
use Livewire\WithFileUploads;

layout('livewire.layouts.app');

uses([WithFileUploads::class]);

state([
    'submission' => null,
    'draft_file' => null, // Untuk U3 upload draft
]);

mount(function($id) {
    $this->submission = FormSubmission::with(['user.group', 'dispositions.fromUser', 'dispositions.toUser'])
        ->findOrFail($id);
    
    // Check authorization
    $user = auth()->user();
    if ($user->isUser() && $this->submission->user_id !== $user->id) {
        abort(403, 'Anda tidak berhak melihat dokumen ini.');
    }
});

$uploadDraftAndSend = function() {
    $this->validate([
        'draft_file' => 'required|file|mimes:doc,docx|max:10240',
    ], [
        'draft_file.required' => 'File draft produk hukum wajib diunggah.',
        'draft_file.mimes' => 'File harus berupa dokumen Word (doc/docx).',
    ]);

    $user = auth()->user();
    $submission = $this->submission;

    if (!$user->isStaf() || $submission->current_stage !== FormSubmission::STAGE_STAF) {
        session()->flash('error', 'Tidak berwenang mengunggah draft pada tahap ini.');
        return;
    }

    $filePath = $this->draft_file->store('form-submissions', 'public');

    // Catat disposisi pengiriman hasil kajian
    SubmissionDisposition::create([
        'form_submission_id' => $submission->id,
        'from_user_id'       => $user->id,
        'to_user_id'         => null,
        'action'             => SubmissionDisposition::ACTION_DISPOSISI,
        'from_stage'         => FormSubmission::STAGE_STAF,
        'to_stage'           => FormSubmission::STAGE_VERIFIKASI_KABAG,
        'catatan'            => 'Draft Produk Hukum hasil kajian telah diunggah.',
        'instruksi'          => ['Laporkan Hasilnya'], // default instruksi
    ]);

    $submission->update([
        'file_draft_produk_hukum' => $filePath,
        'current_stage'           => FormSubmission::STAGE_VERIFIKASI_KABAG,
        'assigned_to'             => null,
    ]);

    $this->draft_file = null;
    session()->flash('message', 'Draft berhasil diunggah dan dikirim ke Kabag Hukum untuk verifikasi.');
    
    // Refresh submission
    $this->submission = FormSubmission::with(['user.group', 'dispositions.fromUser', 'dispositions.toUser'])->find($submission->id);
};

$markAsDiundangkan = function() {
    $user = auth()->user();
    $submission = $this->submission;

    if (!$user->isStaf() || $submission->current_stage !== FormSubmission::STAGE_SELESAI) {
        session()->flash('error', 'Tidak berwenang menandai tahap ini.');
        return;
    }

    SubmissionDisposition::create([
        'form_submission_id' => $submission->id,
        'from_user_id'       => $user->id,
        'to_user_id'         => null,
        'action'             => 'update_status',
        'from_stage'         => FormSubmission::STAGE_SELESAI,
        'to_stage'           => FormSubmission::STAGE_DIUNDANGKAN,
        'catatan'            => 'Produk Hukum telah resmi diundangkan.',
    ]);

    $submission->update([
        'current_stage' => FormSubmission::STAGE_DIUNDANGKAN,
        'status' => 'diundangkan',
        'tanggal_diundangkan' => now(),
    ]);

    session()->flash('message', 'Status usulan berhasil diperbarui menjadi Telah Diundangkan.');
    $this->submission = FormSubmission::with(['user.group', 'dispositions.fromUser', 'dispositions.toUser'])->find($submission->id);
};

?>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-surface rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
                <div class="p-6 md:p-8 text-gray-900">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 border-b border-border-subtle pb-6 gap-4">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Detail Pendaftaran</h2>
                            <p class="text-sm text-gray-500 mt-1">Lacak progress dan detail usulan produk hukum</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            {{-- AKSI KONTEKSTUAL --}}
                            @if($submission->canBeActedBy(auth()->user()))
                                {{-- Disposisi Button (U5, U4, U2) --}}
                                @if(in_array($submission->current_stage, [FormSubmission::STAGE_SEKDA, FormSubmission::STAGE_ASISTEN, FormSubmission::STAGE_KABAG_HUKUM]))
                                    <a href="{{ route('disposisi.form', $submission->id) }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-md transition-all">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                        Beri Disposisi
                                    </a>
                                @endif
                                
                                {{-- Verifikasi Button (U2, U4, U5) --}}
                                @if(in_array($submission->current_stage, [FormSubmission::STAGE_VERIFIKASI_KABAG, FormSubmission::STAGE_VERIFIKASI_ASISTEN, FormSubmission::STAGE_VERIFIKASI_SEKDA]))
                                    <a href="{{ route('verifikasi.form', $submission->id) }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-md transition-all">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Verifikasi Dokumen
                                    </a>
                                @endif

                                {{-- Persetujuan Button (U6) --}}
                                @if($submission->current_stage === FormSubmission::STAGE_BUPATI)
                                    <a href="{{ route('persetujuan.form', $submission->id) }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-xl shadow-md transition-all">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                                        Persetujuan Bupati
                                    </a>
                                @endif

                                {{-- Update Diundangkan (U3) --}}
                                @if($submission->current_stage === FormSubmission::STAGE_SELESAI && auth()->user()->isStaf())
                                    <button wire:click="markAsDiundangkan" wire:confirm="Yakin ingin menandai usulan ini telah diundangkan?" class="inline-flex items-center px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-xl shadow-md transition-all">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                        Tandai Diundangkan
                                    </button>
                                @endif
                            @endif

                            <a href="{{ route('form-submissions.index') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-page hover:bg-surface-hover text-gray-700 text-sm font-semibold rounded-xl border border-border-subtle transition-all duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                                Kembali
                            </a>
                        </div>
                    </div>
                    
                    @if($submission)
                    
                    {{-- Form Upload Draft untuk U3 --}}
                    @if($submission->current_stage === FormSubmission::STAGE_STAF && (auth()->user()->isStaf() || auth()->user()->hasDevAccess()))
                        <div class="mb-8 p-6 bg-blue-50 border border-blue-200 rounded-2xl">
                            <div class="flex items-start gap-4">
                                <div class="p-3 bg-blue-100 rounded-xl">
                                    <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-blue-900 mb-1">Unggah Draft Produk Hukum (Kajian Staf)</h3>
                                    <p class="text-sm text-blue-700 mb-4">Silakan unggah dokumen hasil pengkajian dan harmonisasi untuk diverifikasi oleh Kabag Hukum.</p>
                                    
                                    <div class="flex flex-col sm:flex-row gap-3 items-end">
                                        <div class="w-full sm:w-auto flex-1">
                                            <input type="file" wire:model="draft_file" accept=".doc,.docx" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                                            @error('draft_file') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <button wire:click="uploadDraftAndSend" 
                                                wire:loading.attr="disabled"
                                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-full transition-colors whitespace-nowrap disabled:opacity-50">
                                            <span wire:loading.remove wire:target="uploadDraftAndSend">Upload & Kirim ke Kabag Hukum</span>
                                            <span wire:loading wire:target="uploadDraftAndSend">Mengunggah...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Reject Notice --}}
                    @if($submission->current_stage === FormSubmission::STAGE_DITOLAK)
                        <div class="mb-8 p-6 bg-red-50 border border-red-200 rounded-2xl flex items-start gap-4">
                            <div class="p-3 bg-red-100 rounded-xl">
                                <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.834-1.964-.834-2.732 0L3.072 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-red-900 mb-1">Usulan Ditolak</h3>
                                <p class="text-sm text-red-700 mb-2">Usulan ini telah ditolak pada tahap: <strong>{{ FormSubmission::getStageLabelMap()[$submission->rejected_at_stage] ?? $submission->rejected_at_stage }}</strong></p>
                                <div class="bg-white/50 rounded-lg p-3 border border-red-100">
                                    <p class="text-sm text-red-800"><span class="font-semibold">Alasan Penolakan:</span> {{ $submission->rejection_note }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Kolom Kiri: Informasi Dasar & Dokumen -->
                        <div class="lg:col-span-2 space-y-8">
                            <div>
                                <h3 class="text-lg font-bold border-b border-gray-100 pb-2 mb-4">Informasi Dasar</h3>
                                
                                <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2">
                                            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Judul RPHD</dt>
                                            <dd class="mt-1 text-base font-medium text-gray-900">{{ $submission->judul_rphd }}</dd>
                                        </div>

                                        @if($submission->nomor_pengajuan)
                                        <div>
                                            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Nomor Pengajuan</dt>
                                            <dd class="mt-1 text-sm text-red-700 font-mono font-bold bg-red-100 inline-flex px-3 py-1 rounded-lg">
                                                {{ $submission->nomor_pengajuan }}
                                            </dd>
                                        </div>
                                        @endif

                                        <div>
                                            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tahap Saat Ini</dt>
                                            <dd class="mt-1">
                                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-md border shadow-2xs {{ $submission->current_stage_color }}">
                                                    {{ $submission->current_stage_label }}
                                                </span>
                                            </dd>
                                        </div>

                                        <div>
                                            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Nomor Permohonan</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ $submission->nomor_permohonan }}</dd>
                                        </div>
                                        
                                        <div>
                                            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Permohonan</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ $submission->tanggal_permohonan->translatedFormat('d F Y') }}</dd>
                                        </div>
                                        
                                        <div class="md:col-span-2">
                                            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Perihal</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ $submission->perihal_permohonan }}</dd>
                                        </div>
                                        
                                        <div>
                                            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">OPD Pengusul</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $submission->pemerintah_daerah }}</dd>
                                        </div>
                                        
                                        <div>
                                            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ $submission->jenis }}</dd>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Files -->
                            <div>
                                <h3 class="text-lg font-bold border-b border-gray-100 pb-2 mb-4">Dokumen Lampiran</h3>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @php
                                        $files = [
                                            ['label' => 'Surat Pengantar', 'path' => $submission->file_1, 'icon' => 'document-text', 'color' => 'blue'],
                                            ['label' => 'Draft Produk Hukum (Awal)', 'path' => $submission->file_2, 'icon' => 'document', 'color' => 'gray'],
                                            ['label' => 'Lampiran', 'path' => $submission->file_3, 'icon' => 'paper-clip', 'color' => 'gray'],
                                            ['label' => 'Kelengkapan Lainnya', 'path' => $submission->file_4, 'icon' => 'folder', 'color' => 'gray'],
                                            ['label' => 'Draft Produk Hukum (Hasil Kajian Staf)', 'path' => $submission->file_draft_produk_hukum, 'icon' => 'check-badge', 'color' => 'emerald'],
                                            ['label' => 'Template NPKMD', 'path' => $submission->file_npkmd, 'icon' => 'clipboard-document-check', 'color' => 'amber'],
                                        ];
                                    @endphp

                                    @foreach($files as $file)
                                        @if($file['path'])
                                        @php
                                            $ext = pathinfo($file['path'], PATHINFO_EXTENSION);
                                            $safeNomor = str_replace('/', '-', $submission->nomor_pengajuan ?? 'Draft');
                                            $safeLabel = str_replace(['(', ')'], '', $file['label']);
                                            $downloadName = $safeLabel . ' - ' . $safeNomor . '.' . $ext;
                                        @endphp
                                        <a href="{{ route('file.download', ['path' => $file['path'], 'name' => $downloadName]) }}" target="_blank" class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-xl hover:border-{{ $file['color'] }}-400 hover:shadow-sm transition-all group">
                                            <div class="p-2 bg-{{ $file['color'] }}-50 rounded-lg group-hover:bg-{{ $file['color'] }}-100 transition-colors">
                                                <svg class="w-5 h-5 text-{{ $file['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            </div>
                                            <span class="text-sm font-medium text-gray-700 flex-1">{{ $file['label'] }}</span>
                                            <svg class="w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Timeline Disposisi -->
                        <div>
                            <h3 class="text-lg font-bold border-b border-gray-100 pb-2 mb-4">Riwayat Perjalanan Dokumen</h3>
                            
                            <div class="relative pl-4 border-l-2 border-gray-200 space-y-6 mt-4">
                                {{-- Initial Submission --}}
                                <div class="relative">
                                    <div class="absolute -left-[21px] top-1 w-3 h-3 bg-gray-300 rounded-full border-2 border-white"></div>
                                    <div class="text-xs text-gray-400">{{ $submission->created_at->translatedFormat('d M Y, H:i') }}</div>
                                    <div class="text-sm font-medium text-gray-900 mt-1">Dokumen Diajukan</div>
                                    <div class="text-xs text-gray-500 mt-0.5">Oleh: {{ $submission->user->name }}</div>
                                </div>

                                {{-- Dispositions --}}
                                @foreach($submission->dispositions as $disp)
                                <div class="relative">
                                    <div class="absolute -left-[21px] top-1 w-3 h-3 {{ $disp->action === 'reject' ? 'bg-red-500' : 'bg-primary' }} rounded-full border-2 border-white"></div>
                                    <div class="text-xs text-gray-400">{{ $disp->created_at->translatedFormat('d M Y, H:i') }}</div>
                                    
                                    <div class="mt-1 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                        <div class="text-sm font-medium text-gray-900 mb-1">
                                            @if($disp->action === 'disposisi')
                                                Disposisi ke {{ $disp->toUser ? $disp->toUser->role_label : 'Level Berikutnya' }}
                                            @elseif($disp->action === 'verifikasi')
                                                Diverifikasi
                                            @elseif($disp->action === 'approve')
                                                Disetujui Bupati
                                            @elseif($disp->action === 'reject')
                                                Ditolak
                                            @elseif($disp->action === 'update_status')
                                                Status Diperbarui
                                            @endif
                                        </div>
                                        
                                        <div class="text-xs text-gray-500 mb-2">Dari: {{ $disp->fromUser->name }} ({{ $disp->fromUser->role_label }})</div>
                                        
                                        @if($disp->instruksi && count($disp->instruksi) > 0)
                                            <div class="mb-2 flex flex-wrap gap-1">
                                                @foreach($disp->instruksi as $inst)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-800">
                                                        {{ $inst }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if($disp->catatan)
                                            <div class="text-xs text-gray-700 italic border-l-2 border-gray-300 pl-2">
                                                "{{ $disp->catatan }}"
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach

                                {{-- Current pending state --}}
                                @if($submission->current_stage !== FormSubmission::STAGE_SELESAI && $submission->current_stage !== FormSubmission::STAGE_DITOLAK && $submission->current_stage !== FormSubmission::STAGE_DIUNDANGKAN)
                                <div class="relative">
                                    <div class="absolute -left-[21px] top-1 w-3 h-3 bg-amber-400 rounded-full border-2 border-white animate-pulse"></div>
                                    <div class="text-sm font-medium text-amber-600 mt-0.5">Menunggu: {{ $submission->current_stage_label }}</div>
                                </div>
                                @endif
                                
                                @if($submission->current_stage === FormSubmission::STAGE_DIUNDANGKAN)
                                <div class="relative">
                                    <div class="absolute -left-[21px] top-1 w-3 h-3 bg-teal-500 rounded-full border-2 border-white"></div>
                                    <div class="text-xs text-gray-400">{{ $submission->tanggal_diundangkan ? \Carbon\Carbon::parse($submission->tanggal_diundangkan)->translatedFormat('d M Y') : '' }}</div>
                                    <div class="text-sm font-bold text-teal-700 mt-1">Selesai & Diundangkan</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
