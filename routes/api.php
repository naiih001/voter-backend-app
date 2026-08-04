<?php

use App\Http\Controllers\CandidateController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\VoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth routes
require __DIR__.'/auth.php';

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Current user
    Route::get('/user', fn (Request $request) => $request->user());

    // Elections — voters see open, admins see all (handled in controller)
    Route::get('/elections', [ElectionController::class, 'index']);
    Route::get('/elections/{election}', [ElectionController::class, 'show']);

    // Positions & candidates — readable by all authenticated users
    Route::get('/positions', [PositionController::class, 'index']);
    Route::get('/positions/{position}', [PositionController::class, 'show']);
    Route::get('/candidates', [CandidateController::class, 'index']);
    Route::get('/candidates/{candidate}', [CandidateController::class, 'show']);

    // Voting
    Route::get('/eligibility', [VoteController::class, 'eligibility']);
    Route::post('/votes', [VoteController::class, 'store']);
    Route::get('/votes/mine', [VoteController::class, 'mine']);

    // Admin-only routes
    Route::middleware('admin')->group(function () {
        // Election management
        Route::post('/elections', [ElectionController::class, 'store']);
        Route::put('/elections/{election}', [ElectionController::class, 'update']);
        Route::delete('/elections/{election}', [ElectionController::class, 'destroy']);
        Route::get('/elections/{election}/results', [ElectionController::class, 'results']);

        // Position management
        Route::post('/positions', [PositionController::class, 'store']);
        Route::put('/positions/{position}', [PositionController::class, 'update']);
        Route::delete('/positions/{position}', [PositionController::class, 'destroy']);

        // Candidate management
        Route::post('/candidates', [CandidateController::class, 'store']);
        Route::put('/candidates/{candidate}', [CandidateController::class, 'update']);
        Route::delete('/candidates/{candidate}', [CandidateController::class, 'destroy']);
    });
});
