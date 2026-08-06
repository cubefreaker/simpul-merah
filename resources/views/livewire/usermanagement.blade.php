<?php

use function Livewire\Volt\{state, computed, rules, mount, layout};
use App\Models\User;
use App\Models\Group;
use Livewire\WithPagination;

layout('livewire.layouts.app');

state([
    'search'   => '',
    'showCreateForm'  => false,
    'showEditForm'    => false,
    'showDeleteModal' => false,
    'deletingUser'  => null,
    'editingUser'   => null,
    'name'     => '',
    'email'    => '',
    'password' => '',
    'password_confirmation' => '',
    'role'     => 'user',
    'group_id' => '',
    'opd_name' => '',
]);

rules([
    'name'     => 'required|string|max:255',
    'email'    => 'required|email|max:255',
    'password' => 'nullable|string|min:8',
    'password_confirmation' => 'nullable|string|min:8',
    'role'     => 'required|in:user,admin,superadmin,asisten,sekda,bupati,dev',
    'group_id' => 'nullable|exists:groups,id',
    'opd_name' => 'nullable|string|max:255',
]);

$users = computed(function() {
    $query = User::with('group');
    
    if ($this->search) {
        $query->where(function($q) {
            $q->where('name', 'like', '%' . $this->search . '%')
              ->orWhere('email', 'like', '%' . $this->search . '%')
              ->orWhereHas('group', function($groupQuery) {
                  $groupQuery->where('name', 'like', '%' . $this->search . '%');
              });
        });
    }
    
    return $query->orderBy('created_at', 'desc')->paginate(10);
});

$groups = computed(function() {
    return Group::orderBy('name')->get();
});

$createUser = function() {
    // Custom validation for password confirmation
    if ($this->password && $this->password !== $this->password_confirmation) {
        $this->addError('password_confirmation', 'Konfirmasi password tidak cocok.');
        return;
    }
    
    $this->validate();
    
    $data = [
        'name'     => $this->name,
        'email'    => $this->email,
        'role'     => $this->role,
        'group_id' => $this->role === 'user' ? ($this->group_id ?: null) : null,
        'opd_name' => $this->role === 'user' ? $this->opd_name : null,
    ];

    
    if ($this->password) {
        $data['password'] = bcrypt($this->password);
    } else {
        $data['password'] = bcrypt('password123'); // Default password
    }
    
    User::create($data);
    
    $this->resetForm();
    $this->showCreateForm = false;
    session()->flash('message', 'User berhasil dibuat!');
};

$editUser = function($id) {
    $this->editingUser = User::findOrFail($id);
    $this->name     = $this->editingUser->name;
    $this->email    = $this->editingUser->email;
    $this->role     = $this->editingUser->role;
    $this->group_id = $this->editingUser->group_id;
    $this->opd_name = $this->editingUser->opd_name ?? '';
    $this->password = '';
    $this->password_confirmation = '';
    $this->showEditForm = true;
};

$updateUser = function() {
    // Custom validation for password confirmation
    if ($this->password && $this->password !== $this->password_confirmation) {
        $this->addError('password_confirmation', 'Konfirmasi password tidak cocok.');
        return;
    }
    
    $this->validate();
    
    $data = [
        'name'     => $this->name,
        'email'    => $this->email,
        'role'     => $this->role,
        'group_id' => $this->role === 'user' ? ($this->group_id ?: null) : null,
        'opd_name' => $this->role === 'user' ? $this->opd_name : null,
    ];

    
    if ($this->password) {
        $data['password'] = bcrypt($this->password);
    }
    
    $this->editingUser->update($data);
    
    $this->resetForm();
    $this->showEditForm = false;
    session()->flash('message', 'User berhasil diperbarui!');
};

$deleteUser = function($id) {
    $user = User::findOrFail($id);
    
    // Prevent deleting self
    if ($user->id === auth()->id()) {
        session()->flash('error', 'Tidak dapat menghapus akun sendiri!');
        return;
    }
    
    $user->delete();
    $this->showDeleteModal = false;
    $this->deletingUser = null;
    session()->flash('message', 'User berhasil dihapus!');
};

$confirmDelete = function($id) {
    $this->deletingUser = User::findOrFail($id);
    $this->showDeleteModal = true;
};

$cancelDelete = function() {
    $this->showDeleteModal = false;
    $this->deletingUser = null;
};

$resetForm = function() {
    $this->name     = '';
    $this->email    = '';
    $this->password = '';
    $this->password_confirmation = '';
    $this->role     = 'user';
    $this->group_id = '';
    $this->opd_name = '';
    $this->editingUser = null;
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
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">Kelola User</h2>
                <button wire:click="$set('showCreateForm', true)" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-md shadow-primary/20 transition-all duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Tambah User</span>
                </button>
            </div>
                    
            <!-- Search -->
            <div class="bg-surface rounded-2xl shadow-sm border border-border-subtle p-5">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input wire:model.live="search" type="text" placeholder="Cari nama, email, atau grup..." 
                           class="block w-full pl-10 pr-4 py-2 text-sm border border-border-subtle rounded-xl bg-page text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                </div>
            </div>
                    
                    <!-- Create/Edit Form Modal -->
                    @if($showCreateForm || $showEditForm)
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center" wire:loading.class="opacity-50 pointer-events-none">
                        <div class="relative mx-auto p-6 w-full max-w-md shadow-xl rounded-2xl bg-surface border border-border-subtle">
                            <div class="mt-3">
                                <h3 class="text-lg font-bold text-gray-900 tracking-tight mb-4">
                                    {{ $showCreateForm ? 'Tambah User' : 'Edit User' }}
                                </h3>
                                
                                <form wire:submit="{{ $showCreateForm ? 'createUser' : 'updateUser' }}" class="space-y-4" wire:loading.class="opacity-50 pointer-events-none">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                                        <input wire:model="name" type="text" 
                                               class="mt-1 block w-full px-4 py-2.5 text-sm border border-border-subtle rounded-xl bg-page text-gray-800 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                                        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <input wire:model="email" type="email" 
                                               class="mt-1 block w-full px-4 py-2.5 text-sm border border-border-subtle rounded-xl bg-page text-gray-800 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                                        @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Password {{ $showEditForm ? '(kosongkan jika tidak ingin mengubah)' : '' }}</label>
                                        <input wire:model="password" type="password" 
                                               class="mt-1 block w-full px-4 py-2.5 text-sm border border-border-subtle rounded-xl bg-page text-gray-800 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                                        @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Konfirmasi Password {{ $showEditForm ? '(kosongkan jika tidak ingin mengubah)' : '' }}</label>
                                        <input wire:model="password_confirmation" type="password" 
                                               class="mt-1 block w-full px-4 py-2.5 text-sm border border-border-subtle rounded-xl bg-page text-gray-800 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                                        @error('password_confirmation') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Role</label>
                                        <select wire:model.live="role" 
                                                class="mt-1 block w-full px-4 py-2.5 text-sm border border-border-subtle rounded-xl bg-page text-gray-800 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                                            <optgroup label="Pejabat Struktural">
                                                <option value="sekda">SEKDA (U5)</option>
                                                <option value="asisten">Asisten I (U4)</option>
                                                <option value="superadmin">Kabag Hukum (U2)</option>
                                                <option value="admin">Staf JF Penyusun (U3)</option>
                                            </optgroup>
                                            <optgroup label="Pengusul">
                                                <option value="user">SKPD / OPD Pengusul (U1)</option>
                                            </optgroup>
                                            <optgroup label="Persetujuan">
                                                <option value="bupati">Bupati (U6)</option>
                                            </optgroup>
                                            @if(app()->environment() !== 'production')
                                            <optgroup label="Development">
                                                <option value="dev">Developer (Dev Only)</option>
                                            </optgroup>
                                            @endif
                                        </select>
                                        @error('role') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Group OPD: hanya tampil jika role = SKPD --}}
                                    @if($role === 'user')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">OPD / Instansi <span class="text-red-500">*</span></label>
                                        <select wire:model="group_id" 
                                                class="mt-1 block w-full px-4 py-2.5 text-sm border border-border-subtle rounded-xl bg-page text-gray-800 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                                            <option value="">Pilih OPD / Instansi</option>
                                            @foreach($this->groups as $group)
                                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('group_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nama OPD (untuk tampilan)</label>
                                        <input wire:model="opd_name" type="text" placeholder="cth: Dinas Pendidikan"
                                               class="mt-1 block w-full px-4 py-2.5 text-sm border border-border-subtle rounded-xl bg-page text-gray-800 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                                        @error('opd_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    @endif
                                    
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
                    @livewire('App\Livewire\LoadingWrapper', ['text' => 'Loading users...'])
                    <table class="min-w-full divide-y divide-border-subtle">
                        <thead class="bg-page">
                            <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">OPD / Grup</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Bergabung</th>
                                <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface divide-y divide-border-subtle">
                            @forelse($this->users as $user)
                            <tr class="hover:bg-surface-hover transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $roleColors = [
                                                'user'       => 'bg-gray-100 text-gray-700',
                                                'admin'      => 'bg-blue-100 text-blue-800',
                                                'superadmin' => 'bg-purple-100 text-purple-800',
                                                'asisten'    => 'bg-orange-100 text-orange-800',
                                                'sekda'      => 'bg-red-100 text-red-800',
                                                'bupati'     => 'bg-yellow-100 text-yellow-900',
                                                'dev'        => 'bg-green-100 text-green-800',
                                            ];
                                            $roleLabels = [
                                                'user'       => 'SKPD / Pengusul',
                                                'admin'      => 'Staf JF Penyusun',
                                                'superadmin' => 'Kabag Hukum',
                                                'asisten'    => 'Asisten I',
                                                'sekda'      => 'SEKDA',
                                                'bupati'     => 'Bupati',
                                                'dev'        => 'Developer',
                                            ];
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $roleColors[$user->role] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ $roleLabels[$user->role] ?? ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->role === 'user')
                                            <div class="text-sm text-gray-900">{{ $user->group ? $user->group->name : ($user->opd_name ?: '—') }}</div>
                                        @else
                                            <div class="text-sm text-gray-400">—</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $user->created_at->format('d/m/Y') }}</div>
                                    </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button wire:click="editUser({{ $user->id }})" 
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
                                        
                                        @if($user->id !== auth()->id())
                                        <button wire:click="confirmDelete({{ $user->id }})" 
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
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        Tidak ada data user
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                <!-- Pagination -->
                <div class="bg-surface px-6 py-4 border-t border-border-subtle">
                    {{ $this->users->links() }}
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
                <p class="text-sm text-gray-700 mb-6">Apakah Anda yakin ingin menghapus user "{{ $deletingUser->name }}"?</p>
                <div class="flex justify-end space-x-3">
                    <button type="button" wire:click="cancelDelete" 
                            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-all duration-150"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50">
                        Batal
                    </button>
                    <button type="button" wire:click="deleteUser({{ $deletingUser->id }})" 
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
