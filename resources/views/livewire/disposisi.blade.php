<?php

use function Livewire\Volt\{state, mount, layout};
use App\Models\FormSubmission;
use App\Models\SubmissionDisposition;
use App\Models\User;

layout('livewire.layouts.app');

state([
    'submission' => null, 
    'catatan' => '',
    'instruksi_disposisi' => []
]);

mount(function(int $id) {
    $submission = FormSubmission::with(['user', 'assignedUser', 'dispositions.fromUser', 'dispositions.toUser'])
        ->findOrFail($id);

    $user = auth()->user();

    // Hanya verifikator yang bisa disposisi
    if (!$user->isVerifikator() && !$user->hasDevAccess()) {
        abort(403, 'Anda tidak berwenang melakukan disposisi.');
    }

    $this->submission = $submission;
});

$doDisposisi = function() {
    $user = auth()->user();
    $submission = $this->submission;

    if (!$user->hasDevAccess() && !$submission->canBeActedBy($user)) {
        session()->flash('error', 'Bukan giliran Anda untuk melakukan disposisi.');
        return;
    }

    if (empty($this->instruksi_disposisi)) {
        $this->addError('instruksi_disposisi', 'Instruksi disposisi harus dipilih minimal satu.');
        return;
    }

    // Tentukan stage berikutnya berdasarkan stage saat ini
    $nextStage = match($submission->current_stage) {
        FormSubmission::STAGE_SEKDA       => FormSubmission::STAGE_ASISTEN,
        FormSubmission::STAGE_ASISTEN     => FormSubmission::STAGE_KABAG_HUKUM,
        FormSubmission::STAGE_KABAG_HUKUM => FormSubmission::STAGE_STAF,
        default => null,
    };

    if (!$nextStage) {
        session()->flash('error', 'Tidak dapat mendisposisi dari stage ini.');
        return;
    }

    // Tentukan penerima disposisi
    $toUser = match($nextStage) {
        FormSubmission::STAGE_ASISTEN     => User::where('role', 'asisten')->first(),
        FormSubmission::STAGE_KABAG_HUKUM => User::where('role', 'superadmin')->first(),
        FormSubmission::STAGE_STAF        => User::where('role', 'admin')->first(),
        default => null,
    };

    // Catat disposisi
    SubmissionDisposition::create([
        'form_submission_id' => $submission->id,
        'from_user_id'       => $user->id,
        'to_user_id'         => $toUser?->id,
        'action'             => SubmissionDisposition::ACTION_DISPOSISI,
        'from_stage'         => $submission->current_stage,
        'to_stage'           => $nextStage,
        'catatan'            => $this->catatan,
        'instruksi'          => $this->instruksi_disposisi,
    ]);

    // Update stage dan assigned_to
    $submission->update([
        'current_stage' => $nextStage,
        'status'        => 'diproses',
        'assigned_to'   => $nextStage === FormSubmission::STAGE_STAF ? $toUser?->id : null,
    ]);

    $this->catatan = '';
    session()->flash('message', 'Disposisi berhasil dikirim!');
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
                    <h2 class="text-xl font-bold text-gray-900">Form Disposisi</h2>
                    <p class="text-sm text-gray-500">Teruskan usulan ke level berikutnya</p>
                </div>
            </div>

            @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                {{ session('error') }}
            </div>
            @endif

            {{-- Info Usulan --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wider mb-4">Detail Usulan</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-400">Nomor Pengajuan</p>
                        <p class="font-mono font-bold text-red-700">{{ $submission->nomor_pengajuan ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Jenis</p>
                        <p class="font-medium text-gray-900">{{ $submission->jenis }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-gray-400">Judul RPHD</p>
                        <p class="font-medium text-gray-900">{{ $submission->judul_rphd }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Pengusul</p>
                        <p class="font-medium text-gray-900">{{ $submission->user->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Tahap Saat Ini</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $submission->current_stage_color }}">
                            {{ $submission->current_stage_label }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Form Disposisi --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wider mb-4">Instruksi Disposisi</h3>

                @php
                    $nextLabel = match($submission->current_stage) {
                        'sekda'       => 'Asisten I',
                        'asisten'     => 'Kepala Bagian Hukum',
                        'kabag_hukum' => 'Staf JF Penyusun',
                        default       => 'Level Berikutnya',
                    };
                @endphp

                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                    <p class="text-sm text-blue-700">
                        Disposisi akan diteruskan ke: <strong>{{ $nextLabel }}</strong>
                    </p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Instruksi Disposisi <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 bg-gray-50 p-4 rounded-xl border border-gray-200">
                        @foreach(FormSubmission::INSTRUKSI_DISPOSISI as $instruksi)
                        <label class="flex items-start gap-2 cursor-pointer group">
                            <div class="flex items-center h-5">
                                <input type="checkbox" wire:model="instruksi_disposisi" value="{{ $instruksi }}"
                                       class="w-4 h-4 text-primary bg-white border-gray-300 rounded focus:ring-primary focus:ring-2 transition-colors">
                            </div>
                            <span class="text-sm text-gray-700 group-hover:text-gray-900">{{ $instruksi }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('instruksi_disposisi')
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Catatan Disposisi <span class="text-gray-400">(opsional)</span>
                    </label>
                    <textarea wire:model="catatan" rows="4"
                              placeholder="Tulis instruksi atau catatan untuk penerima disposisi..."
                              class="block w-full px-4 py-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all resize-none"></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('form-submissions.show', $submission->id) }}" wire:navigate
                       class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-all">
                        Batal
                    </a>
                    <button wire:click="doDisposisi"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-not-allowed"
                            class="px-6 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-md shadow-primary/20 transition-all flex items-center gap-2">
                        <span wire:loading.remove>
                            <svg class="w-4 h-4 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Kirim Disposisi ke {{ $nextLabel }}
                        </span>
                        <span wire:loading class="flex items-center gap-2">
                            <div class="loading-spinner loading-spinner-sm"></div>
                            Mengirim...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
