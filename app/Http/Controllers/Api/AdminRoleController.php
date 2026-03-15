<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Yajra\DataTables\Facades\DataTables;

class AdminRoleController extends Controller
{
    public function rolesDataTables(Request $request): JsonResponse
    {
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'asl_model_has_roles');
        $rolesTable = config('permission.table_names.roles', 'asl_roles');
        $userModelClass = User::class;

        $query = Role::query()
            ->withCount('permissions')
            ->select("{$rolesTable}.*")
            ->selectRaw(
                "(SELECT COUNT(*) FROM {$modelHasRolesTable} WHERE {$modelHasRolesTable}.role_id = {$rolesTable}.id AND {$modelHasRolesTable}.model_type = ?) AS users_count",
                [$userModelClass]
            )
            ->orderBy('name')
            ->with('permissions:id,name');

        /** @var JsonResponse $response */
        $response = DataTables::eloquent($query)->toJson();

        return $response;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Role::query()->orderBy('name');

        if ($request->boolean('with_permissions')) {
            $query->with('permissions');
        }

        $roles = $query->get();

        return $this->apiSuccess($roles, 'Roles fetched successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:asl_roles,name'],
            'guard_name' => ['nullable', 'string', 'max:255'],
        ]);

        $role = Role::query()->create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
        ]);

        return $this->apiSuccess($role->load('permissions'), 'Role created successfully.', 201);
    }

    public function show(Role $role): JsonResponse
    {
        return $this->apiSuccess($role->load('permissions'), 'Role fetched successfully.');
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:asl_roles,name,'.$role->id],
            'guard_name' => ['nullable', 'string', 'max:255'],
        ]);

        $role->update([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? $role->guard_name,
        ]);

        return $this->apiSuccess($role->fresh()->load('permissions'), 'Role updated successfully.');
    }

    public function destroy(Role $role): JsonResponse
    {
        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this->apiSuccess(null, 'Role deleted successfully.');
    }

    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'permission_ids' => ['nullable', 'array', 'required_without:permission_names'],
            'permission_ids.*' => ['string', 'exists:asl_permissions,id'],
            'permission_names' => ['nullable', 'array', 'required_without:permission_ids'],
            'permission_names.*' => ['string', 'exists:asl_permissions,name'],
        ]);

        if (! empty($validated['permission_ids'])) {
            $permissions = Permission::query()->whereIn('id', $validated['permission_ids'])->get();
        } else {
            $permissions = Permission::query()->whereIn('name', $validated['permission_names'] ?? [])->get();
        }

        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this->apiSuccess($role->fresh()->load('permissions'), 'Permissions synced successfully.');
    }
}