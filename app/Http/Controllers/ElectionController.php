<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ElectionController extends Controller
{
    /**
     * List elections (voters see open only, admins see all).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Election::withCount('positions');

        if (! $request->user()->isAdmin()) {
            $query->open()->active();
        }

        return response()->json($query->orderByDesc('created_at')->get());
    }

    /**
     * Show election with positions and candidates.
     */
    public function show(Election $election): JsonResponse
    {
        return response()->json(
            $election->load([
                'positions' => fn ($q) => $q->with('candidates'),
            ])
        );
    }

    /**
     * Create election (admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ]);

        $election = Election::create([
            ...$validated,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);
        AuditLog::create(['user_id' => auth('sanctum')->id(), 'action' => "Created election: {$election->title}"]);

        return response()->json([
            'message' => 'Election created.',
            'election' => $election,
        ], 201);
    }

    /**
     * Update election (admin only).
     */
    public function update(Request $request, Election $election): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', 'in:draft,open,closed'],
            'start_time' => ['sometimes', 'required', 'date'],
            'end_time' => ['sometimes', 'required', 'date', 'after:start_time'],
        ]);

        $election->update($validated);
        AuditLog::create(['user_id' => auth('sanctum')->id(), 'action' => "Updated election: {$election->title}"]);

        return response()->json([
            'message' => 'Election updated.',
            'election' => $election,
        ]);
    }

    /**
     * Delete election (admin only). Soft delete.
     */
    public function destroy(Election $election): JsonResponse
    {
        $title = $election->title;
        $election->delete();
        AuditLog::create(['user_id' => auth('sanctum')->id(), 'action' => "Deleted election: {$title}"]);

        return response()->json([
            'message' => 'Election deleted.',
        ]);
    }

    /**
     * Election results (admin only).
     */
    public function results(Election $election): JsonResponse
    {
        $positions = $election->positions()
            ->with(['candidates' => fn ($q) => $q->withCount('votes')])
            ->orderBy('title')
            ->get();

        $results = $positions->map(fn ($position) => [
            'position' => $position->title,
            'total_votes' => $position->candidates->sum('votes_count'),
            'candidates' => $position->candidates->map(fn ($c) => [
                'name' => $c->name,
                'votes' => $c->votes_count,
            ])->values(),
        ]);

        return response()->json([
            'election' => $election->title,
            'results' => $results,
        ]);
    }
}
