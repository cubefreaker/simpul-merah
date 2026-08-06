<?php

use function Livewire\Volt\{state, computed, rules, mount, layout};
use App\Models\Group;
use Livewire\WithPagination;

layout('livewire.layouts.app');

state([
    'search' => '',
    'showCreateForm' => false,
    'showEditForm' => false,
    'showDeleteModal' => false,
    'deletingGroup' => null,
    'editingGroup' => null,
    'name' => '',
    'description' => '',
]);

rules([
    'name' => 'required|string|max:255|unique:groups,name',
    'description' => 'nullable|string|max:1000',
]);

$groups = computed(function() {
    $query = Group::query()
        ->when($this->search, function($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
        })
        ->withCount('users')
        ->orderBy('created_at', 'desc');
    
    return $query->paginate(10);
});

$createGroup = function() {
    $this->validate();
    
    Group::create([
        'name' => $this->name,
        'description' => $this->description,
    ]);
    
    $this->resetForm();
    $this->showCreateForm = false;
    session()->flash('message', 'Grup berhasil dibuat!');
};

$editGroup = function($id) {
    $this->editingGroup = Group::findOrFail($id);
    $this->name = $this->editingGroup->name;
    $this->description = $this->editingGroup->description;
    $this->showEditForm = true;
};

$updateGroup = function() {
    $this->validate([
        'name' => 'required|string|max:255|unique:groups,name,' . $this->editingGroup->id,
        'description' => 'nullable|string|max:1000',
    ]);
    
    $this->editingGroup->update([
        'name' => $this->name,
        'description' => $this->description,
    ]);
    
    $this->resetForm();
    $this->showEditForm = false;
    session()->flash('message', 'Grup berhasil diperbarui!');
};

$deleteGroup = function($id) {
    $group = Group::findOrFail($id);
    
    // Check if group has users
    if ($group->users()->count() > 0) {
        session()->flash('error', 'Tidak dapat menghapus grup yang memiliki user!');
        return;
    }
    
    $group->delete();
    $this->showDeleteModal = false;
    $this->deletingGroup = null;
    session()->flash('message', 'Grup berhasil dihapus!');
};

$confirmDelete = function($id) {
    $this->deletingGroup = Group::findOrFail($id);
    $this->showDeleteModal = true;
};

$cancelDelete = function() {
    $this->showDeleteModal = false;
    $this->deletingGroup = null;
};

$resetForm = function() {
    $this->name = '';
    $this->description = '';
    $this->editingGroup = null;
};

$cancelForm = function() {
    $this->resetForm();
    $this->showCreateForm = false;
    $this->showEditForm = false;
};

?>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">Kelola OPD / Instansi</h2>
                    <p class="text-sm text-gray-500 mt-1">Daftar OPD/Instansi pengusul produk hukum di Kabupaten Sampang</p>
                </div>
                <button wire:click="$set('showCreateForm', true)" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-md shadow-primary/20 transition-all duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Tambah Group</span>
                </button>
            </div>
                    
            <!-- Search -->
            <div class="bg-surface rounded-2xl shadow-sm border border-border-subtle p-5">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input wire:model.live="search" type="text" placeholder="Cari nama atau deskripsi group..." 
                           class="block w-full pl-10 pr-4 py-2 text-sm border border-border-subtle rounded-xl bg-page text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                </div>
            </div>
                    
                    <!-- Create/Edit Form Modal -->
                    @if($showCreateForm || $showEditForm)
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center" wire:loading.class="opacity-50 pointer-events-none">
                        <div class="relative mx-auto p-6 w-full max-w-md shadow-xl rounded-2xl bg-surface border border-border-subtle">
                            <div class="mt-3">
                                <h3 class="text-lg font-bold text-gray-900 tracking-tight mb-4">
                                    {{ $showCreateForm ? 'Tambah Group' : 'Edit Group' }}
                                </h3>
                                
                                <form wire:submit="{{ $showCreateForm ? 'createGroup' : 'updateGroup' }}" class="space-y-4" wire:loading.class="opacity-50 pointer-events-none">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nama Group</label>
                                        <input wire:model="name" type="text" 
                                               class="mt-1 block w-full px-4 py-2.5 text-sm border border-border-subtle rounded-xl bg-page text-gray-800 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                                        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                        <textarea wire:model="description" rows="3"
                                                  class="mt-1 block w-full px-4 py-2.5 text-sm border border-border-subtle rounded-xl bg-page text-gray-800 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all"></textarea>
                                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div class="flex justify-end space-x-3 pt-4">
                                        <button type="button" wire:click="cancelForm" 
                                                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-all duration-150"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50">
                                            Batal
                                        </button>
                                        <button type="submit" 
                                                class="px-4 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-md shadow-primary/20 transition-all duration-150 flex items-center justify-center"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-75 cursor-not-allowed">
                                            <span wire:loading.remove>{{ $showCreateForm ? 'Simpan' : 'Update' }}</span>
                                            <span wire:loading.delay>
                                                <div class="flex items-center space-x-2">
                                                    <div class="loading-spinner loading-spinner-sm"></div>
                                                    <span>{{ $showCreateForm ? 'Menyimpan...' : 'Mengupdate...' }}</span>
                                                </div>
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                    
            <!-- Table -->
            <div class="bg-surface rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
                <div class="overflow-x-auto relative">
                    @livewire('App\Livewire\LoadingWrapper', ['text' => 'Loading groups...'])
                    <table class="min-w-full divide-y divide-border-subtle">
                        <thead class="bg-page">
                            <tr>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama OPD / Instansi</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah User</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dibuat</th>
                                <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface divide-y divide-border-subtle">
                            @forelse($this->groups as $group)
                            <tr class="hover:bg-surface-hover transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $group->name }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ $group->description ?: '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $group->users_count }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $group->created_at->format('d/m/Y') }}</div>
                                    </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button wire:click="editGroup({{ $group->id }})" 
                                                class="inline-flex items-center p-2 rounded-lg text-amber-600 hover:bg-amber-50 transition-colors cursor-pointer"
                                                title="Edit"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50">
                                            <span wire:loading.remove>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </span>
                                            <span wire:loading>
                                                <div class="loading-spinner loading-spinner-sm inline-block"></div>
                                            </span>
                                        </button>
                                        
                                        <button wire:click="confirmDelete({{ $group->id }})" 
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
                                    </div>
                                </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        Tidak ada data group
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                <!-- Pagination -->
                <div class="bg-surface px-6 py-4 border-t border-border-subtle">
                    {{ $this->groups->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center" wire:loading.class="opacity-50 pointer-events-none">
        <div class="relative mx-auto p-6 w-full max-w-md shadow-xl rounded-2xl bg-surface border border-border-subtle">
            <div class="mt-3">
                <h3 class="text-lg font-bold text-gray-900 tracking-tight mb-4">Konfirmasi Hapus</h3>
                <p class="text-sm text-gray-700 mb-6">Apakah Anda yakin ingin menghapus group "{{ $deletingGroup->name }}"?</p>
                <div class="flex justify-end space-x-3">
                    <button type="button" wire:click="cancelDelete" 
                            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-all duration-150"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50">
                        Batal
                    </button>
                    <button type="button" wire:click="deleteGroup({{ $deletingGroup->id }})" 
                            class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-red-500/20 transition-all duration-150 flex items-center justify-center"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-not-allowed">
                        <span wire:loading.remove>Hapus</span>
                        <span wire:loading.delay>
                            <div class="flex items-center space-x-2">
                                <div class="loading-spinner loading-spinner-sm"></div>
                                <span>Menghapus...</span>
                            </div>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
