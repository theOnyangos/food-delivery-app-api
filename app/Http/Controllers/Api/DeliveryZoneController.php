<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryZone\StoreDeliveryZoneRequest;
use App\Http\Requests\DeliveryZone\UpdateDeliveryZoneRequest;
use App\Models\DeliveryZone;
use App\Services\DeliveryZoneService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryZoneController extends Controller
{
    public function __construct(
        private readonly DeliveryZoneService $deliveryZoneService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $zones = $this->deliveryZoneService->listForAdmin($request->only(['status', 'zip_code']));

        return $this->apiSuccess($zones, 'Delivery zones fetched successfully.');
    }

    public function store(StoreDeliveryZoneRequest $request): JsonResponse
    {
        $zone = DeliveryZone::query()->create([
            'name' => $request->validated('name'),
            'zip_code' => $request->validated('zip_code'),
            'delivery_fee' => $request->validated('delivery_fee'),
            'status' => $request->validated('status', 'active'),
            'minimum_order_amount' => $request->validated('minimum_order_amount'),
            'estimated_delivery_minutes' => $request->validated('estimated_delivery_minutes'),
            'is_serviceable' => $request->validated('is_serviceable', true),
        ]);

        $this->notifyAdmins(
            type: 'delivery_zone_created',
            title: 'Delivery Zone Created',
            message: sprintf('Delivery zone "%s" (%s) was created.', $zone->name, $zone->zip_code),
            actor: $request->user(),
            payload: [
                'delivery_zone_id' => $zone->id,
                'name' => $zone->name,
                'zip_code' => $zone->zip_code,
                'status' => $zone->status,
            ]
        );

        return $this->apiSuccess($zone, 'Delivery zone created successfully.', 201);
    }

    public function show(DeliveryZone $deliveryZone): JsonResponse
    {
        return $this->apiSuccess($deliveryZone, 'Delivery zone fetched successfully.');
    }

    public function update(UpdateDeliveryZoneRequest $request, DeliveryZone $deliveryZone): JsonResponse
    {
        $deliveryZone->fill($request->validated())->save();

        $this->notifyAdmins(
            type: 'delivery_zone_updated',
            title: 'Delivery Zone Updated',
            message: sprintf('Delivery zone "%s" (%s) was updated.', $deliveryZone->name, $deliveryZone->zip_code),
            actor: $request->user(),
            payload: [
                'delivery_zone_id' => $deliveryZone->id,
                'name' => $deliveryZone->name,
                'zip_code' => $deliveryZone->zip_code,
                'status' => $deliveryZone->status,
            ]
        );

        return $this->apiSuccess($deliveryZone->fresh(), 'Delivery zone updated successfully.');
    }

    public function destroy(Request $request, DeliveryZone $deliveryZone): JsonResponse
    {
        $zoneData = [
            'delivery_zone_id' => $deliveryZone->id,
            'name' => $deliveryZone->name,
            'zip_code' => $deliveryZone->zip_code,
            'status' => $deliveryZone->status,
        ];

        $deliveryZone->delete();

        $this->notifyAdmins(
            type: 'delivery_zone_deleted',
            title: 'Delivery Zone Deleted',
            message: sprintf('Delivery zone "%s" (%s) was deleted.', $zoneData['name'], $zoneData['zip_code']),
            actor: $request->user(),
            payload: $zoneData,
        );

        return $this->apiSuccess(null, 'Delivery zone deleted successfully.');
    }

    public function checkCoverage(Request $request): JsonResponse
    {
        $zipcode = (string) $request->query('zip_code', '');

        if ($zipcode === '') {
            return $this->apiError('zip_code is required.', 422, [
                'errors' => ['zip_code' => ['The zip_code field is required.']],
            ]);
        }

        $result = $this->deliveryZoneService->checkCoverage($zipcode);

        return $this->apiSuccess($result, $result['message']);
    }

    private function notifyAdmins(string $type, string $title, string $message, mixed $actor, array $payload = []): void
    {
        $admins = $this->notificationService->getAdminUsers();

        foreach ($admins as $admin) {
            $this->notificationService->create($admin, $type, array_merge($payload, [
                'title' => $title,
                'message' => $message,
                'performed_by' => [
                    'id' => $actor?->id,
                    'email' => $actor?->email,
                    'name' => $actor?->full_name,
                ],
            ]));
        }
    }
}
