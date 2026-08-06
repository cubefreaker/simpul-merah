<?php

use function Livewire\Volt\{state, mount, layout};
use App\Models\User;

layout('livewire.layouts.app');

state(['users' => [], 'impersonating' => null]);

mount(function() {
    $user = auth()->user();

    if (!$user->hasDevAccess()) {
        abort(403, 'Halaman ini hanya untuk Developer di environment non-production.');
    }

    $this->users = User::orderBy('role')->get();
    $this->impersonating = session('impersonating_original_id') ? User::find(session('impersonating_original_id')) : null;
});

$impersonate = function(int $userId) {
    $originalUser = auth()->user();

    if (!$originalUser->hasDevAccess()) {
        abort(403);
    }

    $targetUser = User::findOrFail($userId);

    // Simpan ID asli dev di session
    session(['impersonating_original_id' => $originalUser->id]);

    auth()->login($targetUser);

    session()->flash('message', "Sekarang Anda login sebagai [{$targetUser->role}] {$targetUser->name}");
    $this->redirect(route('dashboard'), navigate: true);
};

$stopImpersonate = function() {
    $originalId = session('impersonating_original_id');
    if (!$originalId) return;

    $originalUser = User::find($originalId);
    session()->forget('impersonating_original_id');

    auth()->login($originalUser);
    $this->redirect(route('dev.impersonate'), navigate: true);
};

?>

<div>
    {{-- Dev Banner --}}
    <div class="bg-green-600 text-white px-4 py-2 text-center text-sm font-semibold">
        ⚙️ DEV MODE — Halaman ini hanya tersedia di environment: <code class="bg-green-700 px-1 rounded">{{ app()->environment() }}</code>
    </div>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Developer — Impersonate User</h2>
                <p class="text-sm text-gray-500 mt-1">Masuk sebagai user lain untuk keperluan testing</p>
            </div>

            @if(session('message'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">
                {{ session('message') }}
            </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 bg-gray-50 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-700">Pilih User untuk Impersonate</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($users as $u)
                        <tr class="hover:bg-gray-50 transition-colors {{ $u->id === auth()->id() ? 'bg-green-50' : '' }}">
                            <td class="px-4 py-3">
                                <div class="flex flex-col items-start gap-1">
                                    <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-full
                                        {{ match($u->role) {
                                            'user'       => 'bg-gray-100 text-gray-700',
                                            'admin'      => 'bg-blue-100 text-blue-700',
                                            'superadmin' => 'bg-purple-100 text-purple-700',
                                            'asisten'    => 'bg-orange-100 text-orange-700',
                                            'sekda'      => 'bg-red-100 text-red-700',
                                            'bupati'     => 'bg-yellow-100 text-yellow-800',
                                            'dev'        => 'bg-green-100 text-green-700',
                                            default      => 'bg-gray-100 text-gray-700',
                                        } }}">
                                        {{ $u->role_label }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 font-mono">kode: {{ $u->role }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $u->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $u->email }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($u->id === auth()->id())
                                    <span class="text-xs text-green-600 font-semibold">← Anda sekarang</span>
                                @else
                                    <button wire:click="impersonate({{ $u->id }})"
                                            class="text-xs px-3 py-1.5 bg-gray-800 hover:bg-black text-white font-semibold rounded-lg transition-colors">
                                        Masuk sebagai ini
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
