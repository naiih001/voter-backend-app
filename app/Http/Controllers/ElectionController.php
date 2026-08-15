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

        // Voters see only elections that are published AND inside their voting
        // window. Admins see everything (drafts, future, and closed too).
        if (! $request->user()->isAdmin()) {
            $query->active();
        }

        return response()->json($query->orderByDesc('created_at')->get());
    }

    /**
     * Show election with positions and candidates.
     */
    public function show(Election $election): JsonResponse
    {
        $user = auth('sanctum')->user();
        $isAdmin = $user?->isAdmin() ?? false;

        abort_unless($isAdmin || $election->isPublished(), 404);

        $election->load(['positions' => fn ($q) => $q->with('candidates')]);
        if ($isAdmin) {
            $election->setAttribute('readiness', $this->readiness($election));
        }
        return response()->json($election);
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
            'start_time' => ['sometimes', 'required', 'date'],
            'end_time' => ['sometimes', 'required', 'date', 'after:start_time'],
        ]);

        if ($election->isPublished()) {
            return response()->json(['message' => 'Unpublish this scheduled election before editing it.'], 409);
        }

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
        if ($election->isPublished() || $election->votes()->exists()) {
            return response()->json(['message' => 'Published elections or elections with votes cannot be deleted.'], 409);
        }
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
        if (! request()->user()->isAdmin() && $election->status !== 'closed') {
            return response()->json(['message' => 'Results become available after the election closes.'], 403);
        }
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

    public function publish(Election $election): JsonResponse
    {
        $readiness = $this->readiness($election);
        if (! $readiness['ready']) {
            return response()->json(['message' => 'Election is not ready to publish.', 'readiness' => $readiness], 409);
        }

        $election->update(['published_at' => now()]);
        AuditLog::create(['user_id' => request()->user()->id, 'action' => "Published election: {$election->title}"]);

        return response()->json(['message' => 'Election published.', 'election' => $election->fresh()]);
    }

    public function unpublish(Election $election): JsonResponse
    {
        if ($election->status !== 'scheduled') {
            return response()->json(['message' => 'Only scheduled elections can be returned to draft.'], 409);
        }

        $election->update(['published_at' => null]);
        AuditLog::create(['user_id' => request()->user()->id, 'action' => "Unpublished election: {$election->title}"]);

        return response()->json(['message' => 'Election returned to draft.', 'election' => $election->fresh()]);
    }

    private function readiness(Election $election): array
    {
        $positions = $election->positions()->withCount('candidates')->get();
        $checks = [
            ['label' => 'Voting window is valid', 'passed' => $election->end_time->gt($election->start_time) && $election->end_time->isFuture()],
            ['label' => 'At least one position exists', 'passed' => $positions->isNotEmpty()],
            ['label' => 'Every position has at least two candidates', 'passed' => $positions->isNotEmpty() && $positions->every(fn ($position) => $position->candidates_count >= 2)],
        ];

        return ['ready' => collect($checks)->every('passed'), 'checks' => $checks];
    }
}
