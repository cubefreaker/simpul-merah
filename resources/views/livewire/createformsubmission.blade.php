<?php

use function Livewire\Volt\{state, rules, mount, layout, uses};
use App\Models\FormSubmission;
use App\Models\Group;
use Livewire\WithFileUploads;

layout('livewire.layouts.app');

uses([WithFileUploads::class]);

state([
    'judul_rphd'         => '',
    'nomor_permohonan'   => '',
    'tanggal_permohonan' => '',
    'perihal_permohonan' => '',
    'pemerintah_daerah'  => '',
    'jenis'              => '',
    'file_1'             => null, // surat pengantar
    'file_2'             => null, // draft produk hukum awal dari pengusul
    'file_3'             => null, // lampiran
    'file_4'             => null, // kelengkapan lainnya
    'jenisOptions'       => [],
    'opdLocked'          => true, // OPD di-lock dari profil user
]);

rules([
    'judul_rphd' => 'required|string|max:255',
    'nomor_permohonan' => 'required|string|max:255',
    'tanggal_permohonan' => 'required|date',
    'perihal_permohonan' => 'required|string|max:255',
    'pemerintah_daerah' => 'required|string|max:100',
    'jenis' => 'required|string|max:255',
    'file_1' => 'nullable|file|mimes:doc,docx|max:10240', // surat pengantar
    'file_2' => 'nullable|file|mimes:doc,docx|max:10240', // draft produk hukum
    'file_3' => 'nullable|file|mimes:doc,docx|max:10240', // lampiran
    'file_4' => 'nullable|file|mimes:doc,docx|max:10240', // kelengkapan lainnya
    // 'file_5' => 'nullable|file|mimes:doc,docx|max:10240',
]);

mount(function () {
    $user = auth()->user();

    // Auto-fill OPD dari profil user yang login (lock)
    if ($user->isSkpd()) {
        $this->pemerintah_daerah = $user->opd_name
            ?? ($user->group?->name)
            ?? '';
    }

    $this->jenisOptions = FormSubmission::getJenisOptions();
});

$save = function () {
    $this->validate();

    $data = [
        'user_id'            => auth()->id(),
        'judul_rphd'         => $this->judul_rphd,
        'nomor_permohonan'   => $this->nomor_permohonan,
        'tanggal_permohonan' => $this->tanggal_permohonan,
        'perihal_permohonan' => $this->perihal_permohonan,
        'pemerintah_daerah'  => $this->pemerintah_daerah,
        'jenis'              => $this->jenis,
        'status'             => 'diproses',
        'current_stage'      => FormSubmission::STAGE_SEKDA, // langsung masuk ke SEKDA
        'nomor_pengajuan'    => FormSubmission::generateNomorPengajuan($this->jenis),
    ];

    // Handle file uploads
    $fileFields = [
        'file_1', // surat pengantar
        'file_2', // draft produk hukum
        'file_3', // lampiran
        'file_4', // kelengkapan lainnya
        // 'file_5',
    ];

    foreach ($fileFields as $field) {
        if ($this->$field) {
            try {
                $filePath = $this->$field->store('form-submissions', 'public');
                $data[$field] = $filePath;

                // Log successful upload
                \Log::info("File uploaded successfully: {$field} -> {$filePath}");
            } catch (\Exception $e) {
                \Log::error("File upload failed for {$field}: " . $e->getMessage());
                session()->flash('error', "Gagal mengupload file {$field}: " . $e->getMessage());
                return;
            }
        }
    }

    try {
        FormSubmission::create($data);
        session()->flash('message', 'Pendaftaran berhasil disimpan!');
        return redirect()->route('form-submissions.index');
    } catch (\Exception $e) {
        \Log::error('Failed to create form submission: ' . $e->getMessage());
        session()->flash('error', 'Gagal menyimpan pendaftaran: ' . $e->getMessage());
    }
};

?>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-surface rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
                <div class="p-6 md:p-8 text-gray-900">
                    <div class="flex justify-between items-center mb-8 border-b border-border-subtle pb-6">
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Formulir Pendaftaran</h2>
                        <a href="{{ route('form-submissions.index') }}" class="inline-flex items-center px-4 py-2 bg-page hover:bg-surface-hover text-gray-700 text-sm font-semibold rounded-xl border border-border-subtle transition-all duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Kembali
                        </a>
                    </div>

                    @if (session()->has('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form wire:submit="save" class="space-y-6" wire:loading.class="opacity-50 pointer-events-none">
                        <!-- Judul RPHD -->
                        <div>
                            <label for="judul_rphd" class="block text-sm font-medium text-gray-700">Judul RPHD</label>
                            <input wire:model="judul_rphd" type="text" id="judul_rphd"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            @error('judul_rphd')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Nomor Permohonan -->
                        <div>
                            <label for="nomor_permohonan" class="block text-sm font-medium text-gray-700">Nomor
                                Permohonan</label>
                            <input wire:model="nomor_permohonan" type="text" id="nomor_permohonan"
                                placeholder="Nomor Surat..."
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            @error('nomor_permohonan')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Tanggal Permohonan -->
                        <div>
                            <label for="tanggal_permohonan" class="block text-sm font-medium text-gray-700">Tanggal
                                Permohonan</label>
                            <input wire:model="tanggal_permohonan" type="date" id="tanggal_permohonan"
                                placeholder="Tanggal Surat..."
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            @error('tanggal_permohonan')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Perihal Permohonan -->
                        <div>
                            <label for="perihal_permohonan" class="block text-sm font-medium text-gray-700">Perihal
                                Permohonan</label>
                            <input wire:model="perihal_permohonan" type="text" id="perihal_permohonan"
                                placeholder="Perihal Surat..."
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            @error('perihal_permohonan')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Organisasi Perangkat Daerah -->
                        <div>
                            <label for="pemerintah_daerah" class="block text-sm font-medium text-gray-700">OPD Pengusul</label>
                            <div class="mt-1 relative">
                                <input type="text"
                                    value="{{ $pemerintah_daerah }}"
                                    readonly
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50 text-gray-700 cursor-not-allowed"
                                    title="OPD diambil otomatis dari profil akun Anda">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">OPD terisi otomatis sesuai profil akun Anda. Hubungi admin jika perlu perubahan.</p>
                            @error('pemerintah_daerah')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Jenis -->
                        <div>
                            <label for="jenis" class="block text-sm font-medium text-gray-700">Jenis</label>
                            <select wire:model="jenis" id="jenis"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Jenis...</option>
                                @foreach ($jenisOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('jenis')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- File Uploads -->
                        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                            <p class="text-sm text-blue-800">
                                <strong>Catatan:</strong> Semua file harus dalam format DOC/DOCX dengan ukuran maksimal 10MB.
                            </p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Surat Pengantar -->
                            <div>
                                <label for="file_1" class="block text-sm font-medium text-gray-700">Surat Pengantar (DOC/DOCX)</label>
                                <input wire:model="file_1" type="file" id="file_1" accept=".doc,.docx"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                @error('file_1')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Draft Produk Hukum -->
                            <div>
                                <label for="file_2" class="block text-sm font-medium text-gray-700">Draft Produk Hukum (DOC/DOCX)</label>
                                <input wire:model="file_2" type="file" id="file_2" accept=".doc,.docx"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                @error('file_2')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Lampiran -->
                            <div>
                                <label for="file_3" class="block text-sm font-medium text-gray-700">Lampiran (DOC/DOCX)</label>
                                <input wire:model="file_3" type="file" id="file_3" accept=".doc,.docx"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                @error('file_3')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Kelengkapan Lainnya -->
                            <div>
                                <label for="file_4" class="block text-sm font-medium text-gray-700">Kelengkapan Lainnya (DOC/DOCX)</label>
                                <input wire:model="file_4" type="file" id="file_4" accept=".doc,.docx"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                @error('file_4')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end pt-6 border-t border-border-subtle">
                            <button type="submit" 
                                    class="inline-flex items-center justify-center px-4 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-md shadow-primary/20 transition-all duration-150"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-75">
                                <span wire:loading.remove>Simpan Pendaftaran</span>
                                <span wire:loading>
                                    <div class="flex items-center space-x-2">
                                        <div class="loading-spinner loading-spinner-sm"></div>
                                        <span>Menyimpan...</span>
                                    </div>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
