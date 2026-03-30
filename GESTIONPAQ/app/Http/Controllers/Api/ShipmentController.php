<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsPlanner;
use App\Support\LogisticsSupport;
use Carbon\Carbon;
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
            'priorities' => [
                ['value' => 'standard', 'label' => 'Estandar'],
                ['value' => 'high', 'label' => 'Alta'],
                ['value' => 'express', 'label' => 'Urgente'],
            ],
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
        $payload = LogisticsPlanner::prepareShipmentContext($this->validated($request, false));

        return DB::transaction(function () use ($payload) {
            $shipmentId = DB::table('paquetes')->insertGetId($this->packageRecord($payload));
            $shipment = $this->afterWrite($shipmentId, $payload);

            return ApiResponder::success($shipment, 201);
        });
    }

    public function update(Request $request, int $shipment): JsonResponse
    {
        if (! DB::table('paquetes')->where('id', $shipment)->exists()) {
            return ApiResponder::error('Envio no encontrado.', 404);
        }

        $current = LogisticsSupport::shipmentPayload(
            LogisticsSupport::shipmentBaseQuery()->where('paquetes.id', $shipment)->first()
        ) ?? [];
        $payload = LogisticsPlanner::prepareShipmentContext(array_merge($current, $this->validated($request, true)));

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
            'tracking' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:100'],
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
        $tracking = trim((string) ($payload['tracking'] ?? ''));

        if ($tracking === '') {
            $tracking = $this->generateTrackingCode();
        }

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
            'status' => $payload['initialStatus'] ?? 'Pendiente',
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

    private function generateTrackingCode(): string
    {
        $yearPrefix = now()->format('y');
        $baseSequence = ((int) $yearPrefix) * 10000;
        $maxSequence = (int) DB::table('paquetes')
            ->where(function ($query) use ($yearPrefix): void {
                $query->where('codigo_tracking', 'like', 'GPQ-'.$yearPrefix.'%')
                    ->orWhere('tracking_code', 'like', 'GPQ-'.$yearPrefix.'%')
                    ->orWhere('codigo_rastreo', 'like', 'GPQ-'.$yearPrefix.'%');
            })
            ->lockForUpdate()
            ->selectRaw('MAX(CAST(SUBSTRING(COALESCE(codigo_tracking, tracking_code, codigo_rastreo), 5) AS UNSIGNED)) as max_sequence')
            ->value('max_sequence');

        $nextSequence = max($baseSequence, $maxSequence) + 1;

        for ($attempt = 0; $attempt < 25; $attempt++) {
            $candidate = 'GPQ-'.str_pad((string) ($nextSequence + $attempt), 6, '0', STR_PAD_LEFT);

            $exists = DB::table('paquetes')
                ->where('codigo_tracking', $candidate)
                ->orWhere('tracking_code', $candidate)
                ->orWhere('codigo_rastreo', $candidate)
                ->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        return 'GPQ-'.now()->format('ymdHis').Str::upper(Str::random(2));
    }

    private function afterWrite(int $shipmentId, array $payload, bool $updated = false): array
    {
        $message = $updated ? 'Envio actualizado correctamente.' : 'Envio creado, pendiente de asignacion automatica.';
        $recommendation = LogisticsPlanner::recommendRouteForShipment($payload);

        if ($recommendation && ! empty($recommendation['needsRouteCreation'])) {
            $recommendation = LogisticsPlanner::materializeRecommendation($recommendation);
        }

        if ($recommendation && ! empty($recommendation['route']['id'])) {
            $route = $recommendation['route'];
            $shipmentStatus = $this->operationalShipmentStatus($route, $payload);
            [$assignmentStatus, $assignmentState] = $this->assignmentStatusPair($shipmentStatus);

            if (! empty($route['driverId']) && ! empty($route['scheduledDate'])) {
                LogisticsPlanner::ensureDriverShiftCoverage((int) $route['driverId'], $route['scheduledDate']);
            }

            $this->persistRecommendedRouteResources($route);
            $sequence = (int) DB::table('asignaciones')
                ->where(function ($query) use ($route): void {
                    $query->where('ruta_id', $route['id'])
                        ->orWhere('route_id', $route['id']);
                })
                ->count() + 1;

            DB::table('asignaciones')->updateOrInsert(
                ['package_id' => $shipmentId],
                [
                    'ruta_id' => $route['id'],
                    'route_id' => $route['id'],
                    'vehiculo_id' => $route['vehicleId'],
                    'vehicle_id' => $route['vehicleId'],
                    'conductor_id' => $route['driverId'],
                    'driver_id' => $route['driverId'],
                    'warehouse_id' => $payload['originWarehouseId'] ?? null,
                    'sequence_order' => $sequence,
                    'status' => $assignmentStatus,
                    'estado' => $assignmentState,
                    'fecha_asignacion' => now(),
                    'fecha_salida' => $shipmentStatus === 'En ruta' ? now() : null,
                    'fecha_llegada_estimada' => $this->estimatedArrivalForRoute($route),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('paquetes')->where('id', $shipmentId)->update([
                'estado_id' => LogisticsSupport::packageStatusIdFor($shipmentStatus),
                'estado' => $shipmentStatus,
                'status' => $shipmentStatus,
                'assigned_at' => now(),
                'promised_date' => $route['scheduledDate'] ?? ($payload['scheduledDate'] ?? null),
                'eta_at' => $this->estimatedArrivalForRoute($route),
                'updated_at' => now(),
            ]);

            LogisticsPlanner::syncRouteMetrics($route['id']);
            $assignmentDescription = 'Envio asignado automaticamente a '.$route['code'].' con score '.$recommendation['score'].' pts.';

            if (! empty($recommendation['reason'])) {
                $assignmentDescription .= ' '.$recommendation['reason'];
            }

            if (! empty($route['driverName']) && strtolower((string) $route['driverName']) !== 'sin conductor') {
                $assignmentDescription .= ' Conductor: '.$route['driverName'].'.';
            }

            LogisticsSupport::recordTrackingEvent(
                $shipmentId,
                'Asignacion',
                $assignmentDescription,
                $route['warehouseName'] ?: 'Mesa de despacho',
                $shipmentStatus,
            );

            if ($shipmentStatus === 'Planificado') {
                $message = $updated
                    ? 'Envio actualizado y planificado automaticamente.'
                    : sprintf('Envio creado y planificado automaticamente en %s.', $route['code']);
            } else {
                $message = $updated
                    ? 'Envio actualizado y reasignado automaticamente.'
                    : sprintf('Envio creado y asignado automaticamente a %s.', $route['code']);
            }
        } elseif (! empty($payload['originWarehouseId'])) {
            $message = $updated
                ? 'Envio actualizado. No se encontro una ruta compatible para asignacion automatica.'
                : 'Envio creado, pendiente de asignacion automatica por falta de capacidad o recursos.';
        }

        $item = LogisticsSupport::shipmentBaseQuery()->where('paquetes.id', $shipmentId)->first();

        return [
            'item' => LogisticsSupport::shipmentPayload($item),
            'message' => $message,
            'recommendation' => $recommendation,
        ];
    }

    private function persistRecommendedRouteResources(array $route): void
    {
        $current = DB::table('rutas')
            ->where('id', $route['id'])
            ->select(['driver_id', 'vehicle_id'])
            ->first();

        if (! $current) {
            return;
        }

        $updates = ['updated_at' => now()];
        $shouldUpdateRoute = false;
        $driverId = (int) ($route['driverId'] ?? 0);
        $vehicleId = (int) ($route['vehicleId'] ?? 0);

        if ($driverId > 0 && (int) ($current->driver_id ?? 0) !== $driverId) {
            $updates['driver_id'] = $driverId;
            $shouldUpdateRoute = true;
        }

        if ($vehicleId > 0 && (int) ($current->vehicle_id ?? 0) !== $vehicleId) {
            $updates['vehicle_id'] = $vehicleId;
            $shouldUpdateRoute = true;
        }

        if ($shouldUpdateRoute) {
            DB::table('rutas')->where('id', $route['id'])->update($updates);
        }

        if ($driverId > 0) {
            $this->syncDriverOperationalState($driverId, $vehicleId ?: null, $route['status'] ?? null);
        }
    }

    private function syncDriverOperationalState(int $driverId, ?int $vehicleId, ?string $routeStatus): void
    {
        $status = $this->driverStatusForRoute($routeStatus);
        $statusId = DB::table('estado_conductor')->where('nombre', $status)->value('id');
        $updates = [
            'status' => $status,
            'updated_at' => now(),
        ];

        if ($statusId) {
            $updates['estado_id'] = $statusId;
        }

        if ($vehicleId) {
            $updates['current_vehicle_id'] = $vehicleId;
        }

        DB::table('conductores')->where('id', $driverId)->update($updates);
    }

    private function driverStatusForRoute(?string $routeStatus): string
    {
        $status = strtolower((string) $routeStatus);

        if (str_contains($status, 'ejec')) {
            return 'En ruta';
        }

        if (str_contains($status, 'prepar')) {
            return 'Activo';
        }

        return 'Disponible';
    }

    private function operationalShipmentStatus(array $route, array $payload): string
    {
        $routeStatus = strtolower((string) ($route['status'] ?? ''));
        $scheduledDate = $this->parseDate($route['scheduledDate'] ?? ($payload['scheduledDate'] ?? null));

        if (str_contains($routeStatus, 'ejec')) {
            return 'En ruta';
        }

        if ($scheduledDate && $scheduledDate->isFuture()) {
            return 'Planificado';
        }

        if (! empty($route['id']) && ! empty($route['vehicleId']) && ! empty($route['driverId'])) {
            return 'Asignado';
        }

        if (! empty($route['id'])) {
            return 'Registrado';
        }

        return 'Pendiente';
    }

    private function assignmentStatusPair(string $shipmentStatus): array
    {
        $normalized = strtolower($shipmentStatus);

        if (str_contains($normalized, 'planific')) {
            return ['planned', 'planificada'];
        }

        if (str_contains($normalized, 'ruta')) {
            return ['in_transit', 'en ruta'];
        }

        if (str_contains($normalized, 'asign')) {
            return ['assigned', 'asignada'];
        }

        return ['assigned', 'programada'];
    }

    private function estimatedArrivalForRoute(array $route): ?string
    {
        $scheduledDate = $this->parseDate($route['scheduledDate'] ?? null);
        $start = $this->parseDate($route['startTime'] ?? null);

        if (! $start && $scheduledDate) {
            $start = $scheduledDate->copy()->setTime(8, 0, 0);
        }

        if (! $start) {
            return null;
        }

        $timeMinutes = max(0, (int) ($route['timeMinutes'] ?? 0));

        return $start->copy()->addMinutes($timeMinutes)->toDateTimeString();
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
