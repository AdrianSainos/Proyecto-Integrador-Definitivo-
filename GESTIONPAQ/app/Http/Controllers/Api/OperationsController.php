<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperationsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shipments = LogisticsSupport::shipmentBaseQueryFor($request)->get()->map(fn ($item) => LogisticsSupport::shipmentPayload($item));

        return ApiResponder::success([
            'overview' => [
                ['label' => 'Despachos en espera', 'value' => $shipments->where('status', 'Pendiente')->count()],
                ['label' => 'Unidades operativas', 'value' => $shipments->pluck('vehicleId')->filter()->unique()->count()],
                ['label' => 'Conductores disponibles', 'value' => $shipments->pluck('driverId')->filter()->unique()->count()],
            ],
            'dispatchQueue' => $shipments->values(),
        ]);
    }
}