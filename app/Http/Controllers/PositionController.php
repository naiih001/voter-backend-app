<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Position;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
     * List positions for a specific election.
     */
    public function byElection(Election $election): JsonResponse
    {
        return response()->json(
            $election->positions()->withCount('candidates')->orderBy('title')->get()
        );
    }

    /**
     * Create a new position (admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'election_id' => ['required', 'exists:elections,id'],
            'title' => ['required', 'string', 'max:255', Rule::unique('positions')->where(fn ($q) => $q->where('election_id', $request->integer('election_id')))],
            'description' => ['nullable', 'string'],
        ]);

        if (Election::findOrFail($validated['election_id'])->isPublished()) {
            return response()->json(['message' => 'Published election ballots are locked.'], 409);
        }

        $position = Position::create($validated);
        AuditLog::create(['user_id' => auth('sanctum')->id(), 'action' => "Created position: {$position->title}"]);

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
        if ($position->election->isPublished()) {
            return response()->json(['message' => 'Published election ballots are locked.'], 409);
        }
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('positions')->where(fn ($q) => $q->where('election_id', $position->election_id))->ignore($position->id)],
            'description' => ['nullable', 'string'],
        ]);

        $position->update($validated);
        AuditLog::create(['user_id' => auth('sanctum')->id(), 'action' => "Updated position: {$position->title}"]);

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
        if ($position->election->isPublished() || $position->votes()->exists()) {
            return response()->json(['message' => 'Published positions or positions with votes cannot be deleted.'], 409);
        }
        $title = $position->title;
        $position->delete();
        AuditLog::create(['user_id' => auth('sanctum')->id(), 'action' => "Deleted position: {$title}"]);

        return response()->json([
            'message' => 'Position removed.',
        ]);
    }
}
