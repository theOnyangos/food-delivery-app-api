<?php

namespace App\Http\Middleware;

use App\Services\DeliveryZoneService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateZipcodeHeader
{
    public function __construct(private readonly DeliveryZoneService $deliveryZoneService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $zipcode = trim((string) $request->header('X-zipcode', ''));

        if ($zipcode === '') {
            return response()->json([
                'success' => false,
                'message' => 'X-zipcode header is required.',
                'data' => ['errors' => ['X-zipcode' => ['The X-zipcode header is required.']]],
            ], 422);
        }

        $zone = $this->deliveryZoneService->resolveActiveZoneByZipcode($zipcode);

        if (! $zone) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery is not available for the supplied zip code.',
                'data' => ['zip_code' => $zipcode],
            ], 403);
        }

        $request->attributes->set('delivery_zone', $zone);
        $request->attributes->set('zip_code', $zipcode);

        return $next($request);
    }
}
