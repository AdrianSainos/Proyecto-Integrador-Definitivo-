<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = LogisticsSupport::shipmentBaseQueryFor($request)
            ->orderByDesc('paquetes.id')
            ->get()
            ->map(fn ($item) => LogisticsSupport::shipmentPayload($item))
            ->values();

        return ApiResponder::success($items->all());
    }

    public function show(Request $request, int $shipment): JsonResponse
    {
        $item = LogisticsSupport::shipmentBaseQueryFor($request)->where('paquetes.id', $shipment)->first();

        if (! $item) {
            return ApiResponder::error('Envio no encontrado.', 404);
        }

        return ApiResponder::success(LogisticsSupport::shipmentPayload($item));
    }

    public function options(): JsonResponse
    {
        $customers = app(CustomerController::class)->index()->getData(true);

        return ApiResponder::success([
            'customers' => $customers,
            'packageTypes' => DB::table('tipo_paquete')->orderBy('nombre')->pluck('nombre')->all(),
            'statuses' => DB::table('estado_paquete')->orderBy('nombre')->pluck('nombre')->all(),
            'priorities' => ['estandar', 'alta', 'urgente'],
            'warehouses' => DB::table('almacenes')->orderBy('nombre')->get()->map(fn ($item) => [
                'id' => (int) $item->id,
                'code' => $item->codigo ?: $item->code,
                'name' => $item->nombre,
                'address' => $item->address,
                'city' => $item->city,
                'state' => $item->state,
                'postalCode' => $item->postal_code,
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validated($request, false);

        return DB::transaction(function () use ($payload) {
            $shipmentId = DB::table('paquetes')->insertGetId($this->packageRecord($payload));
            $shipment = $this->afterWrite($shipmentId, $payload);

            return ApiResponder::success($shipment, 201);
        });
    }

    public function update(Request $request, int $shipment): JsonResponse
    {
        $payload = $this->validated($request, true);

        if (! DB::table('paquetes')->where('id', $shipment)->exists()) {
            return ApiResponder::error('Envio no encontrado.', 404);
        }

        return DB::transaction(function () use ($payload, $shipment) {
            DB::table('paquetes')->where('id', $shipment)->update($this->packageRecord($payload, true));
            $response = $this->afterWrite($shipment, $payload, true);

            return ApiResponder::success($response);
        });
    }

    public function destroy(int $shipment): JsonResponse
    {
        DB::table('asignaciones')->where('package_id', $shipment)->delete();
        DB::table('paquetes')->where('id', $shipment)->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $partial): array
    {
        $rules = [
            'tracking' => [$partial ? 'sometimes' : 'required', 'string', 'max:100'],
            'senderId' => [$partial ? 'sometimes' : 'required', 'integer'],
            'recipientId' => [$partial ? 'sometimes' : 'required', 'integer'],
            'originWarehouseId' => ['nullable', 'integer'],
            'originAddress' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'weightKg' => ['nullable', 'numeric'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'volumeM3' => ['nullable', 'numeric'],
            'scheduledDate' => ['nullable', 'date'],
            'packageType' => ['nullable', 'string', 'max:60'],
            'priority' => ['nullable', 'string', 'max:40'],
            'initialStatus' => ['nullable', 'string', 'max:50'],
            'declaredValue' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'destinationAddressId' => ['nullable', 'integer'],
            'destinationAddress' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'destinationCity' => [$partial ? 'sometimes' : 'required', 'string', 'max:120'],
            'destinationState' => [$partial ? 'sometimes' : 'required', 'string', 'max:120'],
            'destinationPostalCode' => [$partial ? 'sometimes' : 'required', 'string', 'max:30'],
        ];

        return $request->validate($rules);
    }

    private function packageRecord(array $payload, bool $partial = false): array
    {
        $statusId = DB::table('estado_paquete')->where('nombre', $payload['initialStatus'] ?? 'Pendiente')->value('id');
        $packageTypeId = DB::table('tipo_paquete')->where('nombre', $payload['packageType'] ?? null)->value('id');
        $tracking = $payload['tracking'] ?? ('CF-'.Str::upper(Str::random(8)));

        $record = [
            'codigo_tracking' => $tracking,
            'tracking_code' => $tracking,
            'codigo_rastreo' => $tracking,
            'cliente_id' => $payload['senderId'] ?? null,
            'sender_id' => $payload['senderId'] ?? null,
            'recipient_id' => $payload['recipientId'] ?? null,
            'origin_warehouse_id' => $payload['originWarehouseId'] ?: null,
            'peso' => $payload['weightKg'] ?? 0,
            'peso_kg' => $payload['weightKg'] ?? 0,
            'weight_kg' => $payload['weightKg'] ?? 0,
            'quantity' => $payload['quantity'] ?? 1,
            'volumen' => $payload['volumeM3'] ?? 0,
            'volumen_m3' => $payload['volumeM3'] ?? 0,
            'volume_m3' => $payload['volumeM3'] ?? 0,
            'tipo_id' => $packageTypeId,
            'package_type' => $payload['packageType'] ?? null,
            'estado_id' => $statusId,
            'estado' => $payload['initialStatus'] ?? 'Pendiente',
            'status' => $payload['initialStatus'] ?? 'pending',
            'priority' => $payload['priority'] ?? 'standard',
            'scheduled_date' => $payload['scheduledDate'] ?? null,
            'descripcion' => $payload['description'] ?? null,
            'description' => $payload['description'] ?? null,
            'declared_value' => $payload['declaredValue'] ?? null,
            'recipient_address_id' => $payload['destinationAddressId'] ?: null,
            'recipient_address' => $payload['destinationAddress'] ?? null,
            'recipient_city' => $payload['destinationCity'] ?? null,
            'recipient_state' => $payload['destinationState'] ?? null,
            'recipient_postal_code' => $payload['destinationPostalCode'] ?? null,
            'notes' => null,
            'updated_at' => now(),
        ];

        if (! $partial) {
            $record['created_at'] = now();
        }

        return $record;
    }

    private function afterWrite(int $shipmentId, array $payload, bool $updated = false): array
    {
        $message = $updated ? 'Envio actualizado correctamente.' : 'Envio creado, pendiente de asignacion automatica.';

        if (! empty($payload['originWarehouseId'])) {
            $route = DB::table('rutas')
                ->where(function ($query) use ($payload): void {
                    $query->where('almacen_origen_id', $payload['originWarehouseId'])
                        ->orWhere('warehouse_id', $payload['originWarehouseId'])
                        ->orWhere('origen_almacen_id', $payload['originWarehouseId']);
                })
                ->orderByDesc('id')
                ->first();

            if ($route) {
                DB::table('asignaciones')->updateOrInsert(
                    ['package_id' => $shipmentId],
                    [
                        'ruta_id' => $route->id,
                        'route_id' => $route->id,
                        'vehiculo_id' => $route->vehicle_id,
                        'vehicle_id' => $route->vehicle_id,
                        'conductor_id' => $route->driver_id,
                        'driver_id' => $route->driver_id,
                        'warehouse_id' => $payload['originWarehouseId'],
                        'status' => 'assigned',
                        'estado' => 'programada',
                        'fecha_asignacion' => now(),
                        'updated_at' => now(),
                    ]
                );

                DB::table('paquetes')->where('id', $shipmentId)->update([
                    'assigned_at' => now(),
                    'updated_at' => now(),
                ]);

                $code = $route->codigo ?: $route->route_code ?: sprintf('RUTA-%04d', $route->id);
                $message = $updated
                    ? 'Envio actualizado correctamente.'
                    : sprintf('Envio creado y asignado automaticamente a %s.', $code);
            }
        }

        $item = LogisticsSupport::shipmentBaseQuery()->where('paquetes.id', $shipmentId)->first();

        return [
            'item' => LogisticsSupport::shipmentPayload($item),
            'message' => $message,
        ];
    }
}