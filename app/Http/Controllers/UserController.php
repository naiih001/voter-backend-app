<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List all users (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::select('id', 'name', 'matric_number', 'email', 'role', 'is_eligible', 'created_at');

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        return response()->json($query->orderBy('name')->get());
    }

    /**
     * Show a single user (admin only).
     */
    public function show(User $user): JsonResponse
    {
        return response()->json(
            $user->only('id', 'name', 'matric_number', 'email', 'role', 'is_eligible', 'created_at')
        );
    }

    /**
     * Update authenticated user's profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated.',
            'user' => $user->select('id', 'name', 'email'),
        ]);
    }

    /**
     * Admin: update any user.
     */
    public function adminUpdate(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['sometimes', 'required', Rule::in(['voter', 'admin'])],
            'is_eligible' => ['sometimes', 'required', 'boolean'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'User updated.',
            'user' => $user->select('id', 'name', 'email', 'role', 'is_eligible'),
        ]);
    }

    /**
     * Admin: delete a user.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted.',
        ]);
    }
}
