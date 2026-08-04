<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Position;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VoteController extends Controller
{
    /**
     * Check whether the authenticated student is eligible to vote for a
     * given position — i.e. their matric number has not already been used.
     */
    public function eligibility(Request $request): JsonResponse
    {
        $request->validate([
            'position_id' => ['required', 'exists:positions,id'],
        ]);

        $user = $request->user();

        if (! $user->isStudent() || ! $user->matric_number) {
            return response()->json([
                'eligible' => false,
                'reason' => 'Only students with a matric number may vote.',
            ], 403);
        }

        $alreadyVoted = Vote::where('matric_number', $user->matric_number)
            ->where('position_id', $request->integer('position_id'))
            ->exists();

        return response()->json([
            'eligible' => ! $alreadyVoted,
            'matric_number' => $user->matric_number,
            'position_id' => $request->integer('position_id'),
            'reason' => $alreadyVoted
                ? 'This matric number has already voted for this position.'
                : null,
        ]);
    }

    /**
     * Cast a vote. Enforces one vote per matric number per position at both
     * the application and database (unique index) levels.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'position_id' => ['required', 'exists:positions,id'],
            'candidate_id' => ['required', 'exists:candidates,id'],
        ]);

        $user = $request->user();

        // Only students with a matric number are eligible voters.
        if (! $user->isStudent() || ! $user->matric_number) {
            throw ValidationException::withMessages([
                'matric_number' => 'Only students with a matric number may vote.',
            ]);
        }

        // The candidate must actually belong to the position being voted for.
        $candidate = Candidate::where('id', $validated['candidate_id'])
            ->where('position_id', $validated['position_id'])
            ->first();

        if (! $candidate) {
            throw ValidationException::withMessages([
                'candidate_id' => 'The selected candidate is not contesting this position.',
            ]);
        }

        // Application-level eligibility check: one vote per matric number per position.
        $alreadyVoted = Vote::where('matric_number', $user->matric_number)
            ->where('position_id', $validated['position_id'])
            ->exists();

        if ($alreadyVoted) {
            throw ValidationException::withMessages([
                'matric_number' => 'This matric number has already voted for this position.',
            ]);
        }

        try {
            $vote = Vote::create([
                'user_id' => $user->id,
                'matric_number' => $user->matric_number,
                'position_id' => $validated['position_id'],
                'candidate_id' => $validated['candidate_id'],
            ]);
        } catch (QueryException $e) {
            // Database-level guarantee: the unique index caught a race condition.
            throw ValidationException::withMessages([
                'matric_number' => 'This matric number has already voted for this position.',
            ]);
        }

        return response()->json([
            'message' => 'Vote cast successfully.',
            'vote' => $vote,
        ], 201);
    }

    /**
     * List the authenticated user's own votes.
     */
    public function mine(Request $request): JsonResponse
    {
        $votes = $request->user()
            ->votes()
            ->with(['position:id,title', 'candidate:id,name'])
            ->latest()
            ->get();

        return response()->json($votes);
    }

    /**
     * Tally results per position and candidate (admin only).
     */
    public function results(): JsonResponse
    {
        $positions = Position::with(['candidates' => function ($query) {
            $query->withCount('votes')->orderByDesc('votes_count');
        }])->orderBy('title')->get();

        $results = $positions->map(fn (Position $position) => [
            'position' => $position->title,
            'total_votes' => $position->candidates->sum('votes_count'),
            'candidates' => $position->candidates->map(fn (Candidate $candidate) => [
                'name' => $candidate->name,
                'votes' => $candidate->votes_count,
            ])->values(),
        ]);

        return response()->json($results);
    }
}
