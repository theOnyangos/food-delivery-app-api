<?php

namespace App\Services;

use App\Models\DeliveryAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DeliveryAddressService
{
    public function __construct(private readonly DeliveryZoneService $deliveryZoneService) {}

    /**
     * @return Collection<int, DeliveryAddress>
     */
    public function listForUser(User $user): Collection
    {
        return DeliveryAddress::query()
            ->where('user_id', $user->id)
            ->with('zone')
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();
    }

    public function createForUser(User $user, array $data): DeliveryAddress
    {
        $zone = $this->deliveryZoneService->resolveActiveZoneByZipcode($data['zip_code']);

        $address = DeliveryAddress::query()->create([
            'user_id' => $user->id,
            'zone_id' => $zone?->id,
            'label' => $data['label'] ?? null,
            'address_line' => $data['address_line'],
            'city' => $data['city'],
            'zip_code' => $data['zip_code'],
            'longitude' => $data['longitude'],
            'latitude' => $data['latitude'],
            'delivery_notes' => $data['delivery_notes'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
            'status' => $data['status'] ?? 'active',
        ]);

        if ($address->is_default) {
            $this->unsetOtherDefaults($user, $address->id);
        }

        return $address->load('zone');
    }

    public function updateForUser(User $user, DeliveryAddress $address, array $data): DeliveryAddress
    {
        $zoneId = $address->zone_id;

        if (isset($data['zip_code'])) {
            $zoneId = $this->deliveryZoneService->resolveActiveZoneByZipcode($data['zip_code'])?->id;
        }

        $address->fill([
            'zone_id' => $zoneId,
            'label' => $data['label'] ?? $address->label,
            'address_line' => $data['address_line'] ?? $address->address_line,
            'city' => $data['city'] ?? $address->city,
            'zip_code' => $data['zip_code'] ?? $address->zip_code,
            'longitude' => $data['longitude'] ?? $address->longitude,
            'latitude' => $data['latitude'] ?? $address->latitude,
            'delivery_notes' => $data['delivery_notes'] ?? $address->delivery_notes,
            'is_default' => array_key_exists('is_default', $data) ? (bool) $data['is_default'] : $address->is_default,
            'status' => $data['status'] ?? $address->status,
        ])->save();

        if ($address->is_default) {
            $this->unsetOtherDefaults($user, $address->id);
        }

        return $address->fresh()->load('zone');
    }

    public function deleteForUser(DeliveryAddress $address): bool
    {
        return (bool) $address->delete();
    }

    private function unsetOtherDefaults(User $user, string $currentAddressId): void
    {
        DeliveryAddress::query()
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentAddressId)
            ->where('is_default', true)
            ->update(['is_default' => false, 'updated_at' => now()]);
    }
}
