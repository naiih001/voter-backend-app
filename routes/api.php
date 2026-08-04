<?php

use App\Http\Controllers\CandidateController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\VoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication routes (register, login, logout, password reset, email verification).
require __DIR__.'/auth.php';

Route::middleware('auth:sanctum')->group(function () {
    // Current authenticated user.
    Route::get('/user', fn (Request $request) => $request->user());

    // Positions & candidates — readable by any authenticated user.
    Route::get('/positions', [PositionController::class, 'index']);
    Route::get('/positions/{position}', [PositionController::class, 'show']);
    Route::get('/candidates', [CandidateController::class, 'index']);
    Route::get('/candidates/{candidate}', [CandidateController::class, 'show']);

    // Voting (students).
    Route::get('/eligibility', [VoteController::class, 'eligibility']);
    Route::post('/votes', [VoteController::class, 'store']);
    Route::get('/votes/mine', [VoteController::class, 'mine']);

    // Admin-only management.
    Route::middleware('admin')->group(function () {
        Route::apiResource('positions', PositionController::class)
            ->except(['index', 'show']);
        Route::apiResource('candidates', CandidateController::class)
            ->except(['index', 'show']);

        // Live results / tallies.
        Route::get('/results', [VoteController::class, 'results']);
    });
});
