<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CandidateController extends Controller
{
    /**
     * List candidates, optionally filtered by position.
     */
    public function index(Request $request): JsonResponse
    {
        $candidates = Candidate::with('position')
            ->when($request->filled('position_id'), fn ($query) => $query->where('position_id', $request->integer('position_id')))
            ->orderBy('name')
            ->get();

        return response()->json($candidates);
    }

    /**
     * List candidates for a specific position.
     */
    public function byPosition(Position $position): JsonResponse
    {
        return response()->json(
            $position->candidates()->orderBy('name')->get()
        );
    }

    /**
     * Add a new candidate (admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'position_id' => ['required', 'exists:positions,id'],
            'name' => ['required', 'string', 'max:255'],
            'matric_number' => [
                'nullable',
                'string',
                'max:50',
                // A person may only contest a given position once.
                Rule::unique('candidates')->where(
                    fn ($query) => $query->where('position_id', $request->input('position_id'))
                ),
            ],
            'manifesto' => ['nullable', 'string'],
        ]);

        $candidate = Candidate::create($validated);

        return response()->json([
            'message' => 'Candidate added.',
            'candidate' => $candidate->load('position'),
        ], 201);
    }

    /**
     * Show a single candidate.
     */
    public function show(Candidate $candidate): JsonResponse
    {
        return response()->json($candidate->load('position'));
    }

    /**
     * Edit a candidate (admin only).
     */
    public function update(Request $request, Candidate $candidate): JsonResponse
    {
        $validated = $request->validate([
            'position_id' => ['sometimes', 'required', 'exists:positions,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'matric_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('candidates')
                    ->where(fn ($query) => $query->where(
                        'position_id',
                        $request->input('position_id', $candidate->position_id)
                    ))
                    ->ignore($candidate->id),
            ],
            'manifesto' => ['nullable', 'string'],
        ]);

        $candidate->update($validated);

        return response()->json([
            'message' => 'Candidate updated.',
            'candidate' => $candidate->load('position'),
        ]);
    }

    /**
     * Remove a candidate (admin only).
     */
    public function destroy(Candidate $candidate): JsonResponse
    {
        $candidate->delete();

        return response()->json([
            'message' => 'Candidate removed.',
        ]);
    }
}
