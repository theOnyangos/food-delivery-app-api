<?php

namespace App\Services;

use App\Models\ChatSupportAllocation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ChatSupportAllocationService
{
    public function listAllocations(?string $supportUserId = null, ?string $vendorUserId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = ChatSupportAllocation::query()
            ->with(['supportUser:id,first_name,middle_name,last_name,email', 'vendorUser:id,first_name,middle_name,last_name,email']);

        if ($supportUserId !== null) {
            $query->where('support_user_id', $supportUserId);
        }
        if ($vendorUserId !== null) {
            $query->where('vendor_user_id', $vendorUserId);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function assign(string $supportUserId, string $vendorUserId): ChatSupportAllocation
    {
        $this->ensureSupportRole($supportUserId);
        $this->ensureVendorUser($vendorUserId);

        $existing = ChatSupportAllocation::query()
            ->where('support_user_id', $supportUserId)
            ->where('vendor_user_id', $vendorUserId)
            ->first();

        if ($existing) {
            return $existing;
        }

        return ChatSupportAllocation::query()->create([
            'support_user_id' => $supportUserId,
            'vendor_user_id' => $vendorUserId,
        ]);
    }

    public function unassign(string $id): bool
    {
        $allocation = ChatSupportAllocation::query()->find($id);
        if (! $allocation) {
            return false;
        }
        $allocation->delete();

        return true;
    }

    /**
     * @return array<int, string>
     */
    public function getSupportUserIdsForVendor(string $vendorUserId): array
    {
        $allocations = ChatSupportAllocation::query()
            ->where('vendor_user_id', $vendorUserId)
            ->pluck('support_user_id')
            ->unique()
            ->values()
            ->all();
        if (count($allocations) > 0) {
            return $allocations;
        }

        return User::query()
            ->role(['Super Admin', 'Admin'])
            ->pluck('id')
            ->all();
    }

    private function ensureSupportRole(string $userId): void
    {
        $user = User::query()->find($userId);
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin'])) {
            throw new \InvalidArgumentException('Support user must have role Super Admin or Admin');
        }
    }

    private function ensureVendorUser(string $userId): void
    {
        $user = User::query()->find($userId);
        if (! $user || ! $user->hasRole('Partner')) {
            throw new \InvalidArgumentException('Vendor user must have role Partner');
        }
    }
}
