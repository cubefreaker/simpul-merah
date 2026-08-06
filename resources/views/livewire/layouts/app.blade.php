<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Livewire Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <!-- Global Loading Overlay using Livewire's native approach -->
        <div wire:loading.delay.longest class="fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center">
            <div class="bg-white rounded-lg p-8 shadow-xl flex flex-col items-center space-y-4">
                <div class="loading-spinner loading-spinner-xl"></div>
                <p class="loading-text">Loading...</p>
            </div>
        </div>

        {{-- ⚠️ DEV MODE BANNER — tampil saat sedang impersonate user lain --}}
        @if(session('impersonating_original_id') && !app()->environment('production'))
        <div class="fixed top-0 left-0 right-0 z-[99999] bg-green-600 text-white py-2 px-4 flex items-center justify-between text-sm font-semibold shadow-lg">
            <div class="flex items-center gap-2">
                <span class="animate-pulse">⚙️</span>
                <span>DEV MODE — Acting as: <strong>[{{ auth()->user()->role }}]</strong> {{ auth()->user()->name }}</span>
            </div>
            <a href="{{ route('dev.stop-impersonate') }}"
               class="bg-white text-green-700 hover:bg-green-50 px-3 py-1 rounded-lg text-xs font-bold transition-colors">
                ↩ Kembali ke Dev
            </a>
        </div>
        {{-- Spacer agar konten tidak tertimpa banner --}}
        <div class="h-9"></div>
        @endif


        <div class="min-h-screen bg-page">
            <!-- Flux Sidebar -->
            <flux:sidebar sticky stashable class="border-e border-sidebar-hover bg-sidebar text-sidebar-text dark:border-zinc-700 dark:bg-zinc-900">
                <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

                <!-- App Header -->
                <div class="flex items-center justify-center h-16 mb-8">
                    <h1 class="text-white font-bold text-xl">SIMPUL MERAH</h1>
                </div>
                
                <!-- Navigation Menu -->
                <flux:navlist variant="outline">
                    <flux:navlist.group heading="Menu" class="grid">
                        <!-- Dashboard -->
                        <flux:navlist.item
                            class="hover:bg-slate-200! hover:text-primary! mb-1!"
                            icon="home" 
                            :href="route('dashboard')" 
                            :current="request()->routeIs('dashboard')" 
                            wire:navigate>
                            Dashboard
                        </flux:navlist.item>
                        
                        <!-- Pendaftaran -->
                        <flux:navlist.item 
                            class="hover:bg-slate-200! hover:text-primary! mb-1!"
                            icon="document-text" 
                            :href="route('form-submissions.index')" 
                            :current="request()->routeIs('form-submissions.*')" 
                            wire:navigate>
                            Pendaftaran
                        </flux:navlist.item>
                        
                        <!-- Kelola User (Superadmin only) -->
                        @if(auth()->user()->isSuperadmin())
                        <flux:navlist.item 
                            class="hover:bg-slate-200! hover:text-primary! mb-1!"
                            icon="users" 
                            :href="route('user-management')" 
                            :current="request()->routeIs('user-management')" 
                            wire:navigate>
                            Kelola User
                        </flux:navlist.item>
                        
                        <!-- Kelola Group (Superadmin only) -->
                        <flux:navlist.item 
                            class="hover:bg-slate-200! hover:text-primary! mb-1!"
                            icon="user-group" 
                            :href="route('group-management')" 
                            :current="request()->routeIs('group-management')" 
                            wire:navigate>
                            Kelola OPD / Instansi
                        </flux:navlist.item>
                        @endif

                        {{-- Dev Tools (dev role, non-production only) --}}
                        @if(auth()->user()->hasDevAccess())
                        <flux:navlist.item 
                            class="hover:bg-green-100! hover:text-green-700! mb-1! border border-green-200!"
                            icon="bug-ant" 
                            :href="route('dev.impersonate')" 
                            :current="request()->routeIs('dev.*')" 
                            wire:navigate>
                            Dev: Impersonate
                        </flux:navlist.item>
                        @endif
                    </flux:navlist.group>
                </flux:navlist>

                <flux:spacer />

                <!-- Bottom Navigation -->
                <flux:navlist variant="outline">
                    <!-- Profil Pengguna -->
                    <flux:navlist.item 
                        class="hover:bg-slate-200! hover:text-primary! mb-1!"
                        icon="user" 
                        :href="route('profile.edit')" 
                        :current="request()->routeIs('profile.*')" 
                        wire:navigate>
                        Profil Pengguna
                    </flux:navlist.item>
                    
                    <!-- Logout -->
                    <flux:navlist.item 
                        class="hover:bg-slate-200! hover:text-primary! mb-1! cursor-pointer!"
                        icon="arrow-right-start-on-rectangle" 
                        as="button" 
                        type="submit" 
                        form="logout-form">
                        Logout
                    </flux:navlist.item>
                </flux:navlist>

                <!-- Hidden Logout Form -->
                <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                    @csrf
                </form>
            </flux:sidebar>

            <!-- Main Content -->
            <flux:main>
                <!-- Top bar for mobile -->
                <flux:header class="lg:hidden">
                    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
                    <flux:spacer />
                    <div class="text-sm text-gray-600">
                        Selamat datang, <span class="font-medium">{{ auth()->user()->name }}</span>
                    </div>
                </flux:header>

                <!-- Page content -->
                <div>
                    <!-- Flash Messages -->
                    @if (session('message'))
                        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('message') }}
                        </div>
                    @endif
                    
                    @if (session('error'))
                        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <!-- Livewire Component Content -->
                    {{ $slot }}
                </div>
            </flux:main>
        </div>
        
        <!-- Livewire Scripts -->
        @livewireScripts
    </body>
</html> 