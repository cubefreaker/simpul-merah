<?php

use function Livewire\Volt\{state, computed, on, mount, layout};
use App\Models\FormSubmission;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

layout('livewire.layouts.app');

state([
    'search' => '', 
    'showStatusModal' => false, 
    'showDeleteModal' => false,
    'selectedSubmission' => null, 
    'deletingSubmission' => null,
    'newStatus' => ''
]);

state(['statusFilter' => ''])->url();

mount(function() {
    // Initialize component
});

$submissions = computed(function() {
    $user = auth()->user();
    $query = FormSubmission::with('user');
    
    if ($user->isUser() || $user->isSkpd()) {
        $query->where('user_id', $user->id);
    }
    
    if ($this->search) {
        $query->where(function($q) {
            $q->where('judul_rphd', 'like', '%' . $this->search . '%')
              ->orWhere('nomor_permohonan', 'like', '%' . $this->search . '%')
              ->orWhere('perihal_permohonan', 'like', '%' . $this->search . '%');
        });
    }
    
    if ($this->statusFilter) {
        $query->where('status', $this->statusFilter);
    }
    
    return $query->orderBy('created_at', 'desc')->paginate(10);
});

$deleteSubmission = function($id) {
    $submission = FormSubmission::findOrFail($id);
    $user = auth()->user();
    
    // Check authorization
    if ($user->isUser() || $user->isSkpd()) {
        if ($submission->user_id !== $user->id) {
            return;
        }
    }
    
    // Delete associated files from storage
    $fileFields = [
        'file_1', // surat pengantar
        'file_2', // draft produk hukum
        'file_3', // lampiran
        'file_4', // kelengkapan lainnya
        // 'file_5'
    ];
    
    foreach ($fileFields as $field) {
        if ($submission->$field && Storage::disk('public')->exists($submission->$field)) {
            Storage::disk('public')->delete($submission->$field);
        }
    }
    
    $submission->delete();
    $this->showDeleteModal = false;
    $this->deletingSubmission = null;
    session()->flash('message', 'Data berhasil dihapus!');
};

$confirmDelete = function($id) {
    $submission = FormSubmission::findOrFail($id);
    $user = auth()->user();
    
    // Check authorization
    if ($user->isUser() && $submission->user_id !== $user->id) {
        return;
    }
    
    if ($user->isAdmin() && $submission->user->group_id !== $user->group_id) {
        return;
    }
    
    $this->deletingSubmission = $submission;
    $this->showDeleteModal = true;
};

$cancelDelete = function() {
    $this->showDeleteModal = false;
    $this->deletingSubmission = null;
};

$openStatusModal = function($submissionId) {
    $submission = FormSubmission::with('user')->findOrFail($submissionId);
    $user = auth()->user();
    
    // Check authorization
    if (!$user->hasDevAccess() && !$user->isSuperadmin() && !$user->isAdmin()) {
        return;
    }
    
    $this->selectedSubmission = $submission;
    $this->newStatus = $submission->status;
    $this->showStatusModal = true;
};

$updateStatus = function() {
    if (!$this->selectedSubmission) {
        return;
    }
    
    $this->selectedSubmission->update(['status' => $this->newStatus]);
    $this->showStatusModal = false;
    $this->selectedSubmission = null;
    $this->newStatus = '';
    
    session()->flash('message', 'Status berhasil diperbarui!');
};

$closeStatusModal = function() {
    $this->showStatusModal = false;
    $this->selectedSubmission = null;
    $this->newStatus = '';
};

?>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Header with Add Button -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">Daftar Pendaftaran</h2>
                <a href="{{ route('form-submissions.create') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-md shadow-primary/20 transition-all duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Tambah Pendaftaran</span>
                </a>
            </div>
                    
            <!-- Search and Filter -->
            <div class="bg-surface rounded-2xl shadow-sm border border-border-subtle p-5">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input wire:model.live="search" type="text" placeholder="Cari berdasarkan judul, nomor, atau perihal..." 
                               class="block w-full pl-10 pr-4 py-2 text-sm border border-border-subtle rounded-xl bg-page text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                               wire:loading.attr="disabled"
                               wire:loading.class="opacity-50">
                    </div>
                    <div class="sm:w-48">
                        <select wire:model.live="statusFilter" class="block w-full px-4 py-2 text-sm border border-border-subtle rounded-xl bg-page text-gray-800 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50">
                            <option value="">Semua Status</option>
                            <option value="belum diproses">Belum Diproses</option>
                            <option value="diproses">Diproses</option>
                            <option value="ditolak">Ditolak</option>
                            <option value="selesai">Selesai</option>
                            <option value="diundangkan">Telah Diundangkan</option>
                        </select>
                    </div>
                </div>
            </div>
                    
            <!-- Table Card -->
            <div class="bg-surface rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
                <div class="overflow-x-auto relative">
                        @livewire('App\Livewire\LoadingWrapper', ['text' => 'Loading submissions...'])
                        <table class="min-w-full divide-y divide-border-subtle">
                            <thead class="bg-page">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Pengusul / Instansi
                                    </th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Judul RPHD
                                    </th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                                        No. Pengajuan
                                    </th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">
                                        Tanggal
                                    </th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Tahap Saat Ini
                                    </th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    @if(auth()->user()->isAdmin() || auth()->user()->isSuperadmin())
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                        Pemohon
                                    </th>
                                    @endif
                                    <th scope="col" class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-surface divide-y divide-border-subtle">
                                @forelse($this->submissions as $submission)
                                <tr class="hover:bg-surface-hover transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $submission->user->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 line-clamp-2" title="{{ $submission->judul_rphd }}">
                                            {{ $submission->judul_rphd }}
                                        </div>
                                        @if($submission->nomor_pengajuan)
                                        <div class="text-xs font-mono font-semibold text-red-700 sm:hidden mt-1">
                                            {{ $submission->nomor_pengajuan }}
                                        </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                                        @if($submission->nomor_pengajuan)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-red-100 text-red-800">
                                            {{ $submission->nomor_pengajuan }}
                                        </span>
                                        @else
                                        <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                        <div class="text-sm text-gray-900">{{ $submission->tanggal_permohonan->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $submission->current_stage_color }}">
                                            {{ $submission->current_stage_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'belum diproses' => 'bg-amber-50 text-amber-800 border-amber-200',
                                                'diproses' => 'bg-blue-50 text-blue-800 border-blue-200',
                                                'ditolak' => 'bg-red-50 text-red-800 border-red-200',
                                                'selesai' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                                'diundangkan' => 'bg-teal-50 text-teal-800 border-teal-200',
                                            ];
                                            $dotColors = [
                                                'belum diproses' => 'bg-amber-500',
                                                'diproses' => 'bg-blue-500',
                                                'ditolak' => 'bg-red-500',
                                                'selesai' => 'bg-emerald-500',
                                                'diundangkan' => 'bg-teal-500',
                                            ];
                                            $statusLabels = [
                                                'belum diproses' => 'Belum Diproses',
                                                'diproses' => 'Diproses',
                                                'ditolak' => 'Ditolak',
                                                'selesai' => 'Selesai',
                                                'diundangkan' => 'Telah Diundangkan',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full border shadow-2xs capitalize {{ $statusColors[$submission->status] }}">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $dotColors[$submission->status] }}"></span>
                                            {{ $statusLabels[$submission->status] }}
                                        </span>
                                    </td>
                                    @if(auth()->user()->isAdmin() || auth()->user()->isSuperadmin())
                                    <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                        <div class="text-sm text-gray-900">{{ $submission->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $submission->user->email }}</div>
                                    </td>
                                    @endif
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="{{ route('form-submissions.show', $submission) }}" 
                                               class="inline-flex items-center p-2 rounded-lg text-primary hover:bg-primary-light/60 transition-colors" title="Lihat">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </a>
                                            
                                            @if(auth()->user()->isAdmin() || auth()->user()->isSuperadmin())
                                            <button wire:click="openStatusModal({{ $submission->id }})" 
                                                    class="inline-flex items-center p-2 rounded-lg text-emerald-600 hover:bg-emerald-50 transition-colors cursor-pointer"
                                                    title="Update Status"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50">
                                                <span wire:loading.remove>
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                </span>
                                                <span wire:loading>
                                                    <div class="loading-spinner loading-spinner-sm inline-block"></div>
                                                </span>
                                            </button>
                                            @endif
                                            
                                            @if($submission->user_id === auth()->id() && $submission->status == 'belum diproses')
                                            <button wire:click="confirmDelete({{ $submission->id }})" 
                                                    class="inline-flex items-center p-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
                                                    title="Hapus"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50">
                                                <span wire:loading.remove>
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </span>
                                                <span wire:loading>
                                                    <div class="loading-spinner loading-spinner-sm inline-block"></div>
                                                </span>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->isAdmin() || auth()->user()->isSuperadmin() ? 7 : 6 }}" class="px-6 py-12 text-center text-gray-500">
                                        Tidak ada data pendaftaran
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="bg-surface px-6 py-4 border-t border-border-subtle">
                        {{ $this->submissions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    @if($showStatusModal && $selectedSubmission)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:loading.class="opacity-50 pointer-events-none">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Update Status</h3>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-2">Judul RPHD:</p>
                    <p class="text-sm font-medium text-gray-900">{{ $selectedSubmission->judul_rphd }}</p>
                </div>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-2">Pemohon:</p>
                    <p class="text-sm font-medium text-gray-900">{{ $selectedSubmission->user->name }}</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Baru</label>
                    <select wire:model="newStatus" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="belum diproses">Belum Diproses</option>
                        <option value="diproses">Diproses</option>
                        <option value="ditolak">Ditolak</option>
                        <option value="selesai">Selesai</option>
                        <option value="diundangkan">Telah Diundangkan</option>
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button wire:click="closeStatusModal" 
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50">
                        Batal
                    </button>
                    <button wire:click="updateStatus" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75">
                        <span wire:loading.remove>Update Status</span>
                        <span wire:loading>
                            <div class="flex items-center space-x-2">
                                <div class="loading-spinner loading-spinner-sm"></div>
                                <span>Updating...</span>
                            </div>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal && $deletingSubmission)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:loading.class="opacity-50 pointer-events-none">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Konfirmasi Hapus</h3>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-2">Apakah Anda yakin ingin menghapus data berikut?</p>
                    <p class="text-sm font-medium text-gray-900">{{ $deletingSubmission->judul_rphd }}</p>
                </div>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-2">Pemohon:</p>
                    <p class="text-sm font-medium text-gray-900">{{ $deletingSubmission->user->name }}</p>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button wire:click="cancelDelete" 
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50">
                        Batal
                    </button>
                    <button wire:click="deleteSubmission({{ $deletingSubmission->id }})" 
                            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75">
                        <span wire:loading.remove>Hapus</span>
                        <span wire:loading>
                            <div class="flex items-center space-x-2">
                                <div class="loading-spinner loading-spinner-sm"></div>
                                <span>Deleting...</span>
                            </div>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
