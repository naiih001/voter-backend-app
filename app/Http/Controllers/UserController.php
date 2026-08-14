<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $user = User::create([...$validated, 'password' => Hash::make($validated['password']), 'role' => User::ROLE_ADMIN, 'matric_number' => null]);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => "Created administrator: {$user->name}"]);
        return response()->json(['message' => 'Administrator created.', 'user' => $user->only('id', 'name', 'email', 'role')], 201);
    }
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

        if ($user->id === $request->user()->id && (($validated['role'] ?? $user->role->value) !== 'admin')) {
            return response()->json(['message' => 'You cannot demote yourself.'], 409);
        }
        if (($validated['role'] ?? $user->role->value) !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json(['message' => 'At least one administrator must remain.'], 409);
        }
        $user->update($validated);
        AuditLog::create(['user_id' => auth('sanctum')->id(), 'action' => "Updated user: {$user->name}"]);

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
        if ($user->id === request()->user()->id || ($user->isAdmin() && User::where('role', 'admin')->count() <= 1)) {
            return response()->json(['message' => 'This administrator cannot be removed.'], 409);
        }
        $name = $user->name;
        $user->delete();
        AuditLog::create(['user_id' => auth('sanctum')->id(), 'action' => "Deleted user: {$name}"]);

        return response()->json([
            'message' => 'User deleted.',
        ]);
    }
}
