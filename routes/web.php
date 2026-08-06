<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Volt::route('dashboard', 'dashboard')->name('dashboard');

    // Loading Demo (for testing)
    Volt::route('loading-demo', 'loading-demo')->name('loading-demo');

    // Test Component
    Volt::route('test-component', 'test-component')->name('test-component');

    // ============================================================
    // Form Submissions — U1 bisa create, semua bisa lihat sesuai role
    // ============================================================
    Volt::route('form-submissions', 'formsubmissions')->name('form-submissions.index');
    Volt::route('form-submissions/create', 'createformsubmission')->name('form-submissions.create');
    Volt::route('form-submissions/{id}', 'showformsubmission')->name('form-submissions.show');

    // ============================================================
    // Download route (untuk force download dengan nama file spesifik)
    // ============================================================
    Route::get('/download-file', function (\Illuminate\Http\Request $request) {
        $path = $request->query('path');
        $name = $request->query('name');
        
        if (!$path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        // Keamanan sederhana: cegah path traversal
        if (str_contains($path, '..')) {
            abort(403, 'Akses ditolak.');
        }

        return response()->download(storage_path('app/public/' . ltrim($path, '/')), $name);
    })->name('file.download');

    // ============================================================
    // Disposisi — U5, U4, U2 (berjenjang ke bawah)
    // + stage.matches.role untuk cegah jumping stage
    // ============================================================
    Route::middleware(['stage.matches.role'])->group(function () {
        Volt::route('disposisi/{id}', 'disposisi')->name('disposisi.form');
        Volt::route('verifikasi/{id}', 'verifikasi')->name('verifikasi.form');
    });

    // ============================================================
    // Persetujuan — khusus U6 (Bupati), diakses saat stage = bupati
    // ============================================================
    Route::middleware(['stage.matches.role'])->group(function () {
        Volt::route('persetujuan/{id}', 'persetujuan')->name('persetujuan.form');
    });

    // ============================================================
    // User Management & Group Management — Superadmin only
    // ============================================================
    Route::middleware(['superadmin'])->group(function () {
        Volt::route('user-management', 'usermanagement')->name('user-management');
        Volt::route('group-management', 'group-management')->name('group-management');
    });

    // ============================================================
    // Dev Tools — hanya untuk role 'dev' di non-production
    // ============================================================
    Route::middleware(['dev.only'])->group(function () {
        Volt::route('dev/impersonate', 'impersonate')->name('dev.impersonate');
    });

    Route::get('dev/stop-impersonate', function() {
        $originalId = session('impersonating_original_id');
        if ($originalId) {
            $originalUser = \App\Models\User::find($originalId);
            session()->forget('impersonating_original_id');
            auth()->login($originalUser);
        }
        return redirect()->route('dev.impersonate');
    })->name('dev.stop-impersonate');

    // ============================================================
    // Settings
    // ============================================================
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // Profile routes
    Volt::route('profile', 'settings.profile')->name('profile.edit');
    Volt::route('profile/password', 'settings.password')->name('profile.password');

    // Artisan helpers (tetap ada, berguna di shared hosting)
    Route::get('/optimize-clear', function () {
        Artisan::call('optimize:clear');
        return 'Optimized and cleared cache';
    });

    // Route::get('/storage-link', function () {
    //     Artisan::call('storage:link');
    //     return 'Storage linked';
    // });

    // Route::get('/migrate', function () {
    //     Artisan::call('migrate');
    //     return 'Migrated';
    // });
});

// DB tools (tanpa auth — hanya untuk deployment/dev, sebaiknya dihapus di production)
Route::get('/wipe', function () {
    Artisan::call('db:wipe', ['--force' => true]);
    return 'Database wiped';
});

Route::get('/migrate', function () {
    Artisan::call('migrate', ['--force' => true]);
    return 'Migrated';
});

Route::get('/seed', function () {
    Artisan::call('db:seed', ['--force' => true]);
    return 'Seeded';
});

require __DIR__.'/auth.php';
