<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIMPUL MERAH - Pemerintah Kabupaten Sampang</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|outfit:500,600,700,800" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased text-gray-900 bg-gray-50 min-h-screen flex flex-col font-sans">
    
    <!-- Navbar -->
    <header class="absolute top-0 w-full z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-24">
                <!-- Logo & Title -->
                <div class="flex items-center gap-4">
                    <img src="{{ asset('images/logo-kab-sampang.webp') }}" alt="Logo Kabupaten Sampang" class="w-12 h-auto drop-shadow-md">
                    <div>
                        <h1 class="text-2xl font-bold font-outfit text-white drop-shadow-md">SIMPUL MERAH</h1>
                        <p class="text-sm text-gray-100 font-medium drop-shadow">Pemerintah Kabupaten Sampang</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="hidden md:flex gap-6 items-center">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-6 py-2.5 rounded-full font-semibold text-sm bg-white text-red-600 hover:bg-gray-50 shadow-lg transition-all transform hover:-translate-y-0.5">
                                Masuk Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-8 py-2.5 rounded-full font-bold text-sm bg-red-600 text-white hover:bg-red-700 shadow-lg shadow-red-900/30 transition-all border border-red-500/50 transform hover:-translate-y-0.5">
                                Login
                            </a>
                        @endauth
                    @endif
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-grow flex items-center relative overflow-hidden">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('images/gedung_setda.png') }}" alt="Gedung Sekretariat Daerah" class="w-full h-full object-cover object-center scale-105 animate-[pulse_30s_ease-in-out_infinite_alternate]" />
            <div class="absolute inset-0 bg-gradient-to-r from-gray-900/90 via-gray-900/70 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full relative z-10 py-32 md:py-48">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-500/20 border border-red-500/30 backdrop-blur-sm mb-6">
                    <span class="flex h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                    <span class="text-xs font-semibold text-red-200 tracking-wider uppercase">Sistem Informasi Pengusulan Produk Hukum Daerah</span>
                </div>
                
                <h2 class="text-4xl md:text-6xl font-extrabold text-white font-outfit leading-tight mb-6 drop-shadow-lg">
                    Transformasi Digital <br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-red-600">Produk Hukum Daerah</span>
                </h2>
                
                <p class="text-lg text-gray-300 mb-10 max-w-2xl leading-relaxed drop-shadow">
                    SIMPUL MERAH mempermudah SKPD di lingkungan Pemerintah Kabupaten Sampang dalam mengusulkan, melacak, dan mengelola penyusunan draft produk hukum secara transparan dan efisien.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-bold text-white bg-red-600 hover:bg-red-700 rounded-2xl shadow-xl shadow-red-900/30 transition-all border border-red-500/50 hover:scale-105 group">
                                Buka Dashboard
                                <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-bold text-white bg-red-600 hover:bg-red-700 rounded-2xl shadow-xl shadow-red-900/30 transition-all border border-red-500/50 hover:scale-105 group">
                                Login Aplikasi
                                <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </main>

    <!-- Features Section (Simplified) -->
    <section class="relative z-20 -mt-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto pb-20">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white/90 backdrop-blur-lg p-8 rounded-3xl shadow-xl border border-gray-100 hover:-translate-y-1 transition-transform">
                <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center mb-6 border border-red-100">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3 class="text-xl font-bold font-outfit text-gray-900 mb-2">Paperless</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Pengusulan draft secara digital, menghemat penggunaan kertas dan ramah lingkungan.</p>
            </div>
            
            <div class="bg-white/90 backdrop-blur-lg p-8 rounded-3xl shadow-xl border border-gray-100 hover:-translate-y-1 transition-transform">
                <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center mb-6 border border-blue-100">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
                <h3 class="text-xl font-bold font-outfit text-gray-900 mb-2">Tracking Mudah</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Lacak posisi usulan produk hukum secara real-time dari tahap pengajuan hingga diundangkan.</p>
            </div>
            
            <div class="bg-white/90 backdrop-blur-lg p-8 rounded-3xl shadow-xl border border-gray-100 hover:-translate-y-1 transition-transform">
                <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center mb-6 border border-emerald-100">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <h3 class="text-xl font-bold font-outfit text-gray-900 mb-2">Aman Terpusat</h3>
                <p class="text-gray-600 text-sm leading-relaxed">Arsip produk hukum tersimpan rapi dan terpusat dengan sistem keamanan berlapis.</p>
            </div>
        </div>
    </section>

    <footer class="bg-gray-900 py-8 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-500 text-sm">
                &copy; {{ date('Y') }} Bagian Hukum Sekretariat Daerah Kabupaten Sampang.<br>
                Hak Cipta Dilindungi Undang-Undang.
            </p>
        </div>
    </footer>
</body>
</html>
