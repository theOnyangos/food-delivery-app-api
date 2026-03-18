<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryAddress\StoreDeliveryAddressRequest;
use App\Http\Requests\DeliveryAddress\UpdateDeliveryAddressRequest;
use App\Models\DeliveryAddress;
use App\Services\DeliveryAddressService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryAddressController extends Controller
{
    public function __construct(
        private readonly DeliveryAddressService $deliveryAddressService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $addresses = $this->deliveryAddressService->listForUser($request->user());

        return $this->apiSuccess($addresses, 'Delivery addresses fetched successfully.');
    }

    public function store(StoreDeliveryAddressRequest $request): JsonResponse
    {
        $address = $this->deliveryAddressService->createForUser($request->user(), $request->validated());

        $this->notifyAdmins(
            type: 'delivery_address_created',
            title: 'Delivery Address Created',
            message: sprintf('A delivery address in %s (%s) was created.', $address->city, $address->zip_code),
            actor: $request->user(),
            payload: [
                'delivery_address_id' => $address->id,
                'user_id' => $address->user_id,
                'zone_id' => $address->zone_id,
                'city' => $address->city,
                'zip_code' => $address->zip_code,
                'status' => $address->status,
            ]
        );

        return $this->apiSuccess($address, 'Delivery address created successfully.', 201);
    }

    public function show(Request $request, DeliveryAddress $deliveryAddress): JsonResponse
    {
        if ((string) $deliveryAddress->user_id !== (string) $request->user()->id) {
            return $this->apiError('This action is unauthorized.', 403);
        }

        return $this->apiSuccess($deliveryAddress->load('zone'), 'Delivery address fetched successfully.');
    }

    public function update(UpdateDeliveryAddressRequest $request, DeliveryAddress $deliveryAddress): JsonResponse
    {
        if ((string) $deliveryAddress->user_id !== (string) $request->user()->id) {
            return $this->apiError('This action is unauthorized.', 403);
        }

        $updated = $this->deliveryAddressService->updateForUser($request->user(), $deliveryAddress, $request->validated());

        $this->notifyAdmins(
            type: 'delivery_address_updated',
            title: 'Delivery Address Updated',
            message: sprintf('A delivery address in %s (%s) was updated.', $updated->city, $updated->zip_code),
            actor: $request->user(),
            payload: [
                'delivery_address_id' => $updated->id,
                'user_id' => $updated->user_id,
                'zone_id' => $updated->zone_id,
                'city' => $updated->city,
                'zip_code' => $updated->zip_code,
                'status' => $updated->status,
            ]
        );

        return $this->apiSuccess($updated, 'Delivery address updated successfully.');
    }

    public function destroy(Request $request, DeliveryAddress $deliveryAddress): JsonResponse
    {
        if ((string) $deliveryAddress->user_id !== (string) $request->user()->id) {
            return $this->apiError('This action is unauthorized.', 403);
        }

        $addressData = [
            'delivery_address_id' => $deliveryAddress->id,
            'user_id' => $deliveryAddress->user_id,
            'zone_id' => $deliveryAddress->zone_id,
            'city' => $deliveryAddress->city,
            'zip_code' => $deliveryAddress->zip_code,
            'status' => $deliveryAddress->status,
        ];

        $this->deliveryAddressService->deleteForUser($deliveryAddress);

        $this->notifyAdmins(
            type: 'delivery_address_deleted',
            title: 'Delivery Address Deleted',
            message: sprintf('A delivery address in %s (%s) was deleted.', $addressData['city'], $addressData['zip_code']),
            actor: $request->user(),
            payload: $addressData,
        );

        return $this->apiSuccess(null, 'Delivery address deleted successfully.');
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
