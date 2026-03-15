<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class AdminUserRoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('roles')->orderBy('email');
        $perPage = (int) $request->input('per_page', 15);
        $perPage = $perPage >= 1 && $perPage <= 100 ? $perPage : 15;

        return $this->apiSuccess($query->paginate($perPage), 'Users fetched successfully.');
    }

    public function updateRoles(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role_ids' => ['nullable', 'array', 'required_without:role_names'],
            'role_ids.*' => ['string', 'exists:asl_roles,id'],
            'role_names' => ['nullable', 'array', 'required_without:role_ids'],
            'role_names.*' => ['string', 'exists:asl_roles,name'],
        ]);

        if (! empty($validated['role_ids'])) {
            $roles = Role::query()->whereIn('id', $validated['role_ids'])->get();
        } else {
            $roles = Role::query()->whereIn('name', $validated['role_names'] ?? [])->get();
        }

        $user->syncRoles($roles);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this->apiSuccess($user->fresh()->load('roles'), 'User roles updated successfully.');
    }
}