<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

 Route::view('/', 'welcome');

// 1. Rute Khusus Lengkapi Profil (Hanya butuh auth, TIDAK butuh profile.completed)
Route::middleware(['auth', 'verified'])->group(function () {
    // Sesuaikan path 'pages.profile.complete' dengan lokasi file volt yang Anda buat di Langkah 4 sebelumnya
    Volt::route('/lengkapi-profil', 'profile.complete-profile')->name('profile.complete');
});


// 2. Grup Utama (Ditambahkan middleware 'profile.completed')
// Semua rute di dalam grup ini akan diblokir jika user belum melengkapi profil
Route::middleware(['auth', 'verified', 'profile.completed'])->group(function () {
    
    Volt::route('/dashboard', 'pages.dashboard')->name('dashboard');
    Volt::route('/alumni', 'pages.alumni.index')->name('alumni.index');
    Volt::route('/alumni/{user}', 'pages.alumni.show')->name('alumni.show');
    Volt::route('/prodi', 'pages.prodi.index')->name('prodi.index');
    Volt::route('/karir', 'pages.karir.index')->name('karir.index');
    Volt::route('/pesan', 'pages.messages.index')->name('messages.index');
    Volt::route('/jobs/{job}', 'jobs.show')->name('loker.show');
    Volt::route('/announcements', 'announcements')->name('announcements');
    
    Route::middleware(['can:mahasiswa-alumni-only'])->group(function () {
        Volt::route('/users', 'volt.admin.users')->name('users');
    });

    // Grup untuk role khusus (Dekan / Admin)
    Route::middleware(['can:admin-only'])->prefix('dekan')->name('admin.')->group(function () {
        // Volt::route('/jobs', 'volt.admin.jobs')->name('jobs');
        Volt::route('/users', 'volt.admin.users')->name('users');
    });

    Route::middleware(['can:admin-only'])->prefix('dekan')->name('dekan.')->group(function () {
        Volt::route('/jobs', 'volt.admin.jobs')->name('jobs');
        // Volt::route('/users', 'volt.admin.users')->name('users');
    });
});

// Standar Profile bawaan Laravel/Breeze
Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';