<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UserAdminService
{
    public function getDataTables(Request $request): mixed
    {
        $query = User::query()
            ->select('asl_users.*')
            ->with('roles')
            ->latest('asl_users.created_at');

        $roleName = $request->query('role');
        if (is_string($roleName) && $roleName !== '') {
            $exists = Role::query()->where('name', $roleName)->exists();
            if ($exists) {
                $query->whereHas('roles', fn ($q) => $q->where('name', $roleName));
            }
        }

        return DataTables::eloquent($query)
            ->addColumn('display_name', function (User $row): string {
                $name = $row->full_name;

                return $name !== '' ? $name : '—';
            })
            ->addColumn('roles_list', function (User $row): string {
                return $row->roles->pluck('name')->sort()->values()->implode(', ') ?: '—';
            })
            ->addColumn('verification_label', function (User $row): string {
                return $row->email_verified_at !== null ? 'Verified' : 'Pending';
            })
            ->addColumn('account_status', function (User $row): string {
                return $row->blocked_at !== null ? 'Blocked' : 'Active';
            })
            ->addColumn('created_at_formatted', fn (User $row) => $row->created_at?->format('Y-m-d H:i') ?? '—')
            ->orderColumn('email', fn ($q, $order) => $q->orderBy('asl_users.email', $order))
            ->orderColumn('display_name', fn ($q, $order) => $q->orderBy('asl_users.last_name', $order)
                ->orderBy('asl_users.first_name', $order))
            ->orderColumn('verification_label', fn ($q, $order) => $q->orderBy('asl_users.email_verified_at', $order === 'asc' ? 'asc' : 'desc'))
            ->orderColumn('account_status', fn ($q, $order) => $q->orderBy('asl_users.blocked_at', $order === 'asc' ? 'desc' : 'asc'))
            ->orderColumn('created_at_formatted', fn ($q, $order) => $q->orderBy('asl_users.created_at', $order))
            ->toJson();
    }
}
