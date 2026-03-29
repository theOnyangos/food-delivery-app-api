<?php

namespace App\Http\Controllers\Api;

use App\Events\PasswordResetLinkRequested;
use App\Events\UserBlockedByAdmin;
use App\Events\UserDeletedByAdmin;
use App\Events\UserUnblockedByAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InviteUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly UserAdminService $userAdminService
    ) {}

    public function index(Request $request): mixed
    {
        return $this->userAdminService->getDataTables($request);
    }

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

    public function show(Request $request, User $user): JsonResponse
    {
        $this->assertCanViewAdminUser($request, $user);

        return $this->apiSuccess(
            $this->formatUserDetail($user->load('roles')),
            'User fetched successfully.'
        );
    }

    public function block(Request $request, User $user): JsonResponse
    {
        $this->assertCanManageUser($request, $user);

        if ($user->blocked_at !== null) {
            return $this->apiError('This account is already blocked.', 422);
        }

        $actor = $request->user();
        if ($actor === null) {
            return $this->apiError('Unauthenticated.', 401);
        }

        $user->forceFill(['blocked_at' => now()])->save();
        $user->tokens()->delete();

        event(new UserBlockedByAdmin($user->fresh(), $actor));

        return $this->apiSuccess($this->formatUserDetail($user->fresh()->load('roles')), 'User blocked successfully.');
    }

    public function unblock(Request $request, User $user): JsonResponse
    {
        $this->assertCanManageUser($request, $user);

        if ($user->blocked_at === null) {
            return $this->apiError('This account is not blocked.', 422);
        }

        $actor = $request->user();
        if ($actor === null) {
            return $this->apiError('Unauthenticated.', 401);
        }

        $user->forceFill(['blocked_at' => null])->save();

        event(new UserUnblockedByAdmin($user->fresh(), $actor));

        return $this->apiSuccess($this->formatUserDetail($user->fresh()->load('roles')), 'User unblocked successfully.');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->assertCanManageUser($request, $user);
        $this->assertCanDeleteUser($request, $user);

        $actor = $request->user();
        if ($actor === null) {
            return $this->apiError('Unauthenticated.', 401);
        }

        event(new UserDeletedByAdmin($user, $actor));

        $user->tokens()->delete();
        $user->delete();

        return $this->apiSuccess(null, 'User account removed successfully.');
    }

    public function requestPasswordReset(Request $request, User $user): JsonResponse
    {
        $this->assertCanManageUser($request, $user);

        event(new PasswordResetLinkRequested($user->email));

        return $this->apiSuccess(null, 'Password reset email has been queued.');
    }

    public function resendInvite(Request $request, User $user): JsonResponse
    {
        $this->assertCanManageUser($request, $user);

        if ($user->email_verified_at !== null) {
            return $this->apiError('This account is already verified.', 422);
        }

        event(new PasswordResetLinkRequested($user->email));

        return $this->apiSuccess(null, 'Invitation email has been queued.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUserDetail(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'account_number' => $user->account_number,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'blocked_at' => $user->blocked_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
            'roles' => $user->roles->map(fn (Role $r): array => [
                'id' => $r->id,
                'name' => $r->name,
            ])->values()->all(),
        ];
    }

    /**
     * Read-only: admins may open their own user detail; cross-user rules still apply for others.
     */
    private function assertCanViewAdminUser(Request $request, User $target): void
    {
        $actor = $request->user();
        if ($actor === null) {
            abort(401);
        }

        if ((string) $actor->id === (string) $target->id) {
            return;
        }

        if ($target->hasRole('Super Admin') && ! $actor->hasRole('Super Admin')) {
            abort(403, 'You cannot manage a Super Admin account.');
        }
    }

    private function assertCanManageUser(Request $request, User $target): void
    {
        $actor = $request->user();
        if ($actor === null) {
            abort(401);
        }

        if ((string) $actor->id === (string) $target->id) {
            abort(422, 'You cannot perform this action on your own account.');
        }

        if ($target->hasRole('Super Admin') && ! $actor->hasRole('Super Admin')) {
            abort(403, 'You cannot manage a Super Admin account.');
        }
    }

    private function assertCanDeleteUser(Request $request, User $target): void
    {
        $actor = $request->user();
        if ($actor === null) {
            abort(401);
        }

        if (! $target->hasRole('Super Admin')) {
            return;
        }

        $superAdminCount = User::query()->role('Super Admin')->count();
        if ($superAdminCount <= 1) {
            abort(422, 'Cannot delete the only Super Admin account.');
        }
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
