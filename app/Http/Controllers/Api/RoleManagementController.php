<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleManagementController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name']);

        return response()->json([
            'roles' => $roles,
        ]);
    }

    public function assign(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'exists:asl_roles,name'],
        ]);

        $requestedRole = $validated['role'];
        $actor = $request->user();

        if ($requestedRole === 'Super Admin' && ! $actor->hasRole('Super Admin')) {
            return response()->json([
                'message' => 'Only Super Admin can assign Super Admin role.',
            ], 403);
        }

        $user->syncRoles([$requestedRole]);

        return response()->json([
            'message' => 'Role assigned successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values(),
            ],
        ]);
    }
}
