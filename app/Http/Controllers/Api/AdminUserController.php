<?php

namespace App\Http\Controllers\Api;

use App\Events\PasswordResetLinkRequested;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InviteUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class AdminUserController extends Controller
{
    public function roleOptions(Request $request): JsonResponse
    {
        $query = Role::query()->orderBy('name');

        if (! $request->user()?->hasRole('Super Admin')) {
            $query->where('name', '!=', 'Super Admin');
        }

        return $this->apiSuccess(
            $query->get(['id', 'name']),
            'Roles fetched successfully.'
        );
    }

    public function store(InviteUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $actor = $request->user();
        if ($actor === null) {
            return $this->apiError('Unauthenticated.', 401);
        }

        $roles = $this->resolveRoles($data);
        $this->assertActorCanAssignRoles($actor, $roles);

        $user = DB::transaction(function () use ($data, $roles): User {
            $user = User::query()->create([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make(Str::password(32)),
                'account_number' => AuthService::generateAccountNumber(),
                'email_verified_at' => null,
                'two_factor_secret' => null,
            ]);

            $user->syncRoles($roles);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $user;
        });

        event(new PasswordResetLinkRequested($user->email));

        return $this->apiSuccess(
            $user->fresh()->load('roles'),
            'Invitation queued. The user will receive an email to set their password shortly.',
            201
        );
    }

    public function resendInvite(User $user): JsonResponse
    {
        if ($user->email_verified_at !== null) {
            return $this->apiError('This account is already verified.', 422);
        }

        event(new PasswordResetLinkRequested($user->email));

        return $this->apiSuccess(null, 'Invitation email has been queued.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, Role>
     */
    private function resolveRoles(array $data): Collection
    {
        if (! empty($data['role_ids'])) {
            return Role::query()->whereIn('id', $data['role_ids'])->get();
        }

        return Role::query()->whereIn('name', $data['role_names'] ?? [])->get();
    }

    /**
     * @param  Collection<int, Role>  $roles
     */
    private function assertActorCanAssignRoles(User $actor, Collection $roles): void
    {
        if ($actor->hasRole('Super Admin')) {
            return;
        }

        foreach ($roles as $role) {
            if ($role->name === 'Super Admin') {
                abort(403, 'You cannot assign the Super Admin role.');
            }
        }
    }
}
