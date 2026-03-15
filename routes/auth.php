<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/**
 * Routes Auth SISKO
 *
 * File ini akan terus dilengkapi seiring modul dikerjakan:
 * - 1.1 Login & Logout    ✅ SELESAI
 * - 1.2 Lupa Password     ← SEKARANG
 * - 1.3 Ganti Password    ← nanti ditambah di sini
 *
 * Catatan: file ini harus di-include di routes/web.php
 * dengan menambahkan: require __DIR__.'/auth.php';
 */

// =========================================================
// GUEST ROUTES — Hanya bisa diakses jika BELUM login
// Jika sudah login → otomatis redirect ke dashboard
// =========================================================

Route::middleware('guest')->group(function () {

    // GET /login → tampilkan halaman login
    Volt::route('/login', 'auth.login')
        ->name('login');

    // GET /forgot-password → tampilkan form input email lupa password
    // Nama route 'password.request' adalah konvensi Laravel — dipakai oleh
    // middleware 'auth' untuk redirect ke sini jika user belum login
    Volt::route('/forgot-password', 'auth.forgot-password')
        ->name('password.request');

    // GET /reset-password/{token} → tampilkan form reset password baru
    // Token dikirim via email, divalidasi oleh Fortify/PasswordBroker
    Volt::route('/reset-password/{token}', 'auth.reset-password')
        ->name('password.reset');
});

// =========================================================
// AUTH ROUTES — Hanya bisa diakses jika SUDAH login
// =========================================================

Route::middleware('auth')->group(function () {

    // POST /logout → proses logout
    // Menggunakan Livewire action, bukan route POST konvensional,
    // karena logout ditrigger via wire:click di komponen logout.blade.php
    //
    // Namun kita tetap sediakan route ini sebagai fallback
    // atau untuk kebutuhan testing via HTTP langsung.
    Route::post('/logout', function () {
        app(\App\Services\AuditTrailService::class)->logLogout();
        \Illuminate\Support\Facades\Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');

    /**
     * Placeholder untuk modul 1.3 (Ganti Password).
     * Akan diisi saat modul tersebut dikerjakan.
     *
     * Volt::route('/ganti-password', 'profile.change-password')
     *     ->name('password.change');
     */
});
