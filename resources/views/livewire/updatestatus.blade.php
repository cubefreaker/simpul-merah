<?php

use function Livewire\Volt\{state, mount};
use App\Models\FormSubmission;

state(['submissionId' => null, 'submission' => null, 'status' => '']);

mount(function($submissionId) {
    $this->submissionId = $submissionId;
    $this->submission = FormSubmission::with('user')->findOrFail($submissionId);
    $this->status = $this->submission->status;
    
    // Check authorization
    $user = auth()->user();
    if (!$user->isAdmin() && !$user->isSuperadmin()) {
        abort(403);
    }
    
    if ($user->isAdmin() && $this->submission->user->group_id !== $user->group_id) {
        abort(403);
    }
});

$updateStatus = function() {
    $this->submission->update(['status' => $this->status]);
    session()->flash('message', 'Status berhasil diperbarui!');
    $this->dispatch('closeModal');
    $this->dispatch('$refresh');
};

$closeModal = function() {
    $this->dispatch('closeModal');
};

?>

<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:loading.class="opacity-50 pointer-events-none">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Update Status</h3>
            
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">Judul RPHD:</p>
                <p class="text-sm font-medium text-gray-900">{{ $submission->judul_rphd }}</p>
            </div>
            
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">Pemohon:</p>
                <p class="text-sm font-medium text-gray-900">{{ $submission->user->name }}</p>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status Baru</label>
                <select wire:model="status" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="belum diproses">Belum Diproses</option>
                    <option value="diproses">Diproses</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button wire:click="closeModal" 
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50">
                    Batal
                </button>
                <button wire:click="updateStatus" 
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center justify-center"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed">
                    <span wire:loading.remove>Update Status</span>
                    <span wire:loading.delay>
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
