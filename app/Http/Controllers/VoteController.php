<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Position;
use App\Models\Vote;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VoteController extends Controller
{
    /**
     * Check whether the authenticated user is eligible to vote for a position.
     */
    public function eligibility(Request $request): JsonResponse
    {
        $request->validate([
            'position_id' => ['required', 'exists:positions,id'],
        ]);

        $user = $request->user();

        if (! $user->is_eligible) {
            return response()->json([
                'eligible' => false,
                'reason' => 'User is not eligible to vote.',
            ], 403);
        }

        $alreadyVoted = Vote::where('voter_id', $user->id)
            ->where('position_id', $request->integer('position_id'))
            ->exists();

        return response()->json([
            'eligible' => ! $alreadyVoted,
            'position_id' => $request->integer('position_id'),
            'reason' => $alreadyVoted ? 'Already voted for this position.' : null,
        ]);
    }

    /**
     * Cast a vote. Enforces one vote per user per position at both
     * the application and database (unique index) levels.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'position_id' => ['required', 'exists:positions,id'],
            'candidate_id' => ['required', 'exists:candidates,id'],
        ]);

        $user = $request->user();

        if (! $user->is_eligible) {
            throw ValidationException::withMessages([
                'voter_id' => 'User is not eligible to vote.',
            ]);
        }

        // Candidate must belong to the position
        $candidate = Candidate::where('id', $validated['candidate_id'])
            ->where('position_id', $validated['position_id'])
            ->first();

        if (! $candidate) {
            throw ValidationException::withMessages([
                'candidate_id' => 'Candidate is not contesting this position.',
            ]);
        }

        // App-level check
        $alreadyVoted = Vote::where('voter_id', $user->id)
            ->where('position_id', $validated['position_id'])
            ->exists();

        if ($alreadyVoted) {
            throw ValidationException::withMessages([
                'position_id' => 'Already voted for this position.',
            ]);
        }

        try {
            $vote = Vote::create([
                'election_id' => $candidate->position->election_id,
                'position_id' => $validated['position_id'],
                'candidate_id' => $validated['candidate_id'],
                'voter_id' => $user->id,
            ]);
        } catch (QueryException $e) {
            throw ValidationException::withMessages([
                'position_id' => 'Already voted for this position.',
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
     * Admin: list all votes with voter and candidate info.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vote::with([
            'voter:id,name,matric_number',
            'position:id,title',
            'candidate:id,name',
        ]);

        if ($request->filled('election_id')) {
            $query->where('election_id', $request->integer('election_id'));
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->integer('position_id'));
        }

        return response()->json($query->latest()->get());
    }

    /**
     * Admin: voting statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $query = Vote::query();

        if ($request->filled('election_id')) {
            $query->where('election_id', $request->integer('election_id'));
        }

        $totalVotes = $query->count();
        $uniqueVoters = (clone $query)->distinct('voter_id')->count('voter_id');
        $byPosition = (clone $query)
            ->select('position_id', \DB::raw('COUNT(*) as vote_count'))
            ->groupBy('position_id')
            ->with('position:id,title')
            ->get();

        return response()->json([
            'total_votes' => $totalVotes,
            'unique_voters' => $uniqueVoters,
            'by_position' => $byPosition,
        ]);
    }
}
