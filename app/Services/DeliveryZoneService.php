<?php

namespace App\Services;

use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Collection;

class DeliveryZoneService
{
    /**
     * @return Collection<int, DeliveryZone>
     */
    public function listForAdmin(array $filters = []): Collection
    {
        $query = DeliveryZone::query()->orderBy('name');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['zip_code'])) {
            $query->where('zip_code', $filters['zip_code']);
        }

        return $query->get();
    }

    public function resolveActiveZoneByZipcode(string $zipcode): ?DeliveryZone
    {
        return DeliveryZone::query()
            ->where('zip_code', $zipcode)
            ->where('status', 'active')
            ->where('is_serviceable', true)
            ->orderBy('name')
            ->first();
    }

    /**
     * @return array{can_deliver: bool, message: string, zone: DeliveryZone|null}
     */
    public function checkCoverage(string $zipcode): array
    {
        $zone = $this->resolveActiveZoneByZipcode($zipcode);

        if (! $zone) {
            return [
                'can_deliver' => false,
                'message' => 'We currently do not deliver to this zip code.',
                'zone' => null,
            ];
        }

        return [
            'can_deliver' => true,
            'message' => 'Delivery is available in this zip code.',
            'zone' => $zone,
        ];
    }
}
