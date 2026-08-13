<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Root - redirect based on auth status
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication pages
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
});

// Authenticated pages
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/candidates', function () {
        return view('candidates');
    })->name('candidates');

    Route::get('/vote', function () {
        return view('vote');
    })->name('vote');

    Route::post('/logout', function () {
        Auth::guard('sanctum')->user()?->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    });
});

// Fallback for SPA-like behavior
Route::fallback(function () {
    return redirect()->route('login');
});
