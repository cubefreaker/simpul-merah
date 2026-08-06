<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use function Livewire\Volt\{state, on, mount, layout};

layout('livewire.layouts.app');

state([
    'name' => '', 
    'email' => '',
    'current_password' => '',
    'password' => '',
    'password_confirmation' => ''
]);

mount(function() {
    $this->name = Auth::user()->name;
    $this->email = Auth::user()->email;
});

$updateProfileInformation = function() {
    $user = Auth::user();

    $validated = $this->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => [
            'required',
            'string',
            'lowercase',
            'email',
            'max:255',
            Rule::unique(User::class)->ignore($user->id)
        ],
    ]);

    $user->fill($validated);

    if ($user->isDirty('email')) {
        $user->email_verified_at = null;
    }

    $user->save();

    $this->dispatch('profile-updated', name: $user->name);
};

$updatePassword = function() {
    try {
        $validated = $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);
    } catch (ValidationException $e) {
        $this->reset('current_password', 'password', 'password_confirmation');
        throw $e;
    }

    Auth::user()->update([
        'password' => Hash::make($validated['password']),
    ]);

    $this->reset('current_password', 'password', 'password_confirmation');

    $this->dispatch('password-updated');
};

$resendVerificationNotification = function() {
    $user = Auth::user();

    if ($user->hasVerifiedEmail()) {
        $this->redirectIntended(default: route('dashboard', absolute: false));
        return;
    }

    $user->sendEmailVerificationNotification();

    Session::flash('status', 'verification-link-sent');
};

?>

<div>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-surface rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
                <div class="p-6 md:p-8 text-gray-900">
                    <div class="mb-8 border-b border-border-subtle pb-6">
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Profile Settings</h2>
                    </div>
                    
                    <form wire:submit="updateProfileInformation" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input wire:model="name" type="text" id="name" required autofocus autocomplete="name" 
                                   class="mt-1 block w-full px-3 py-2 border border-border-subtle rounded-xl bg-page text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input wire:model="email" type="email" id="email" required autocomplete="email" 
                                   class="mt-1 block w-full px-3 py-2 border border-border-subtle rounded-xl bg-page text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            
                            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                                <div class="mt-4">
                                    <p class="text-sm text-gray-600">
                                        Your email address is unverified.
                                        <button type="button" wire:click="resendVerificationNotification" 
                                                class="text-blue-600 hover:text-blue-500 underline">
                                            Click here to re-send the verification email.
                                        </button>
                                    </p>

                                    @if (session('status') === 'verification-link-sent')
                                        <p class="mt-2 text-sm font-medium text-green-600">
                                            A new verification link has been sent to your email address.
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex items-center justify-end pt-2">
                            <button type="submit" 
                                    class="inline-flex items-center justify-center px-4 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-md shadow-primary/20 transition-all duration-150"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-75">
                                <span wire:loading.remove>Save Profile</span>
                                <span wire:loading>
                                    <div class="flex items-center space-x-2">
                                        <div class="loading-spinner loading-spinner-sm"></div>
                                        <span>Saving...</span>
                                    </div>
                                </span>
                            </button>
                        </div>
                    </form>

                    <!-- Password Change Section -->
                    <div class="mt-8 pt-8 border-t border-border-subtle">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Update Password</h3>
                        <p class="text-sm text-gray-600 mb-6">Ensure your account is using a long, random password to stay secure</p>
                        
                        <form wire:submit="updatePassword" class="space-y-6">
                            <!-- Current Password -->
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                <input wire:model="current_password" type="password" id="current_password" required autocomplete="current-password" 
                                       class="mt-1 block w-full px-3 py-2 border border-border-subtle rounded-xl bg-page text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                                @error('current_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- New Password -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                                <input wire:model="password" type="password" id="password" required autocomplete="new-password" 
                                       class="mt-1 block w-full px-3 py-2 border border-border-subtle rounded-xl bg-page text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                                @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                <input wire:model="password_confirmation" type="password" id="password_confirmation" required autocomplete="new-password" 
                                       class="mt-1 block w-full px-3 py-2 border border-border-subtle rounded-xl bg-page text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                                @error('password_confirmation') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="flex items-center justify-end pt-2">
                                <button type="submit" 
                                        class="inline-flex items-center justify-center px-4 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl shadow-md shadow-primary/20 transition-all duration-150"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-75">
                                    <span wire:loading.remove>Update Password</span>
                                    <span wire:loading>
                                        <div class="flex items-center space-x-2">
                                            <div class="loading-spinner loading-spinner-sm"></div>
                                            <span>Updating...</span>
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
</div>
