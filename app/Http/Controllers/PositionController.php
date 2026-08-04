<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    /**
     * List all positions with their candidates.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Position::withCount('candidates')->orderBy('title')->get()
        );
    }

    /**
     * Create a new position (admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'election_id' => ['required', 'exists:elections,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $position = Position::create($validated);

        return response()->json([
            'message' => 'Position created.',
            'position' => $position,
        ], 201);
    }

    /**
     * Show a single position with its candidates.
     */
    public function show(Position $position): JsonResponse
    {
        return response()->json(
            $position->load('candidates')
        );
    }

    /**
     * Update a position (admin only).
     */
    public function update(Request $request, Position $position): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255', 'unique:positions,title,'.$position->id],
            'description' => ['nullable', 'string'],
        ]);

        $position->update($validated);

        return response()->json([
            'message' => 'Position updated.',
            'position' => $position,
        ]);
    }

    /**
     * Delete a position (admin only). Cascades to its candidates and votes.
     */
    public function destroy(Position $position): JsonResponse
    {
        $position->delete();

        return response()->json([
            'message' => 'Position removed.',
        ]);
    }
}
