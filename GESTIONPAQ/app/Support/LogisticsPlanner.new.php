<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LogisticsPlanner
{
    public static function prepareShipmentContext(array $payload): array
    {
        $shipment = $payload;
        $shipment['priority'] = LogisticsSupport::normalizePriority($payload['priority'] ?? null) ?: 'standard';
        $shipment['scheduledDate'] = self::parseDate($payload['scheduledDate'] ?? null)?->toDateString() ?? now()->toDateString();
        $shipment['weightKg'] = max(0, (float) ($payload['weightKg'] ?? 0));
        $shipment['quantity'] = max(1, (int) ($payload['quantity'] ?? 1));
        $shipment['volumeM3'] = max(0, (float) ($payload['volumeM3'] ?? 0));
        $shipment['declaredValue'] = max(0, (float) ($payload['declaredValue'] ?? 0));

        $sender = self::clientProfile((int) ($payload['senderId'] ?? 0));
        $recipient = self::clientProfile((int) ($payload['recipientId'] ?? 0));
        $destination = self::destinationProfile($payload, $recipient);

        $shipment['senderServiceLevel'] = self::normalizeServiceLevel($sender->nivel_servicio ?? null);
        $shipment['recipientServiceLevel'] = self::normalizeServiceLevel($recipient->nivel_servicio ?? null);
        $shipment['serviceLevel'] = $shipment['recipientServiceLevel']
            ?: $shipment['senderServiceLevel']
            ?: ($shipment['priority'] === 'express' ? 'premium' : 'standard');
        $shipment['senderLatitude'] = self::numeric($sender->latitude ?? null);
        $shipment['senderLongitude'] = self::numeric($sender->longitude ?? null);
        $shipment['recipientLatitude'] = self::numeric($recipient->latitude ?? null);
        $shipment['recipientLongitude'] = self::numeric($recipient->longitude ?? null);
        $shipment['destinationLatitude'] = $destination['latitude'];
        $shipment['destinationLongitude'] = $destination['longitude'];
        $shipment['destinationAddress'] = $destination['address'] ?: ($payload['destinationAddress'] ?? '');
        $shipment['destinationCity'] = $destination['city'] ?: ($payload['destinationCity'] ?? '');
        $shipment['destinationState'] = $destination['state'] ?: ($payload['destinationState'] ?? '');
        $shipment['destinationPostalCode'] = $destination['postalCode'] ?: ($payload['destinationPostalCode'] ?? '');
        $shipment['destinationAddressId'] = $destination['id'] ?: ($payload['destinationAddressId'] ?? null);

        $warehouse = self::warehouseProfile((int) ($payload['originWarehouseId'] ?? 0));

        if (! $warehouse) {
            $warehouse = self::resolveWarehouseForShipment($shipment);

            if ($warehouse) {
                $shipment['originWarehouseAutoResolved'] = true;
            }
        }

        if ($warehouse) {
            $shipment['originWarehouseId'] = (int) $warehouse->id;
            $shipment['originAddress'] = trim((string) ($payload['originAddress'] ?? '')) !== ''
                ? $payload['originAddress']
                : self::warehouseAddress($warehouse);
            $shipment['originWarehouseName'] = (string) ($warehouse->nombre ?? $warehouse->code ?? $warehouse->codigo ?? '');
            $shipment['originWarehouseLatitude'] = self::numeric($warehouse->latitude ?? null);
            $shipment['originWarehouseLongitude'] = self::numeric($warehouse->longitude ?? null);
        }

        return $shipment;
    }

    public static function recommendRouteForShipment(array $payload): ?array
    {
        $shipment = self::prepareShipmentContext($payload);
        $warehouseId = (int) ($shipment['originWarehouseId'] ?? 0);

        if (! $warehouseId) {
            return null;
        }

        $requestedDate = self::parseDate($shipment['scheduledDate'] ?? null) ?: now();
        $driverPool = self::candidateDrivers($requestedDate);

        $existingCandidates = self::candidateRoutes($warehouseId)
            ->map(function ($route) use ($shipment, $requestedDate, $driverPool): array {
                $driverRecommendation = self::resolveDriverCandidate($route, $requestedDate, $driverPool);
                $evaluation = self::evaluateCandidate($route, $shipment, $requestedDate, $driverRecommendation);
                $routePayload = LogisticsSupport::routePayload($route);

                if ($driverRecommendation) {
                    $routePayload['driverId'] = $driverRecommendation['id'];
                    $routePayload['driverName'] = $driverRecommendation['name'];
                    $routePayload['driverStatus'] = $driverRecommendation['status'];
                }

                return [
                    'accepted' => $evaluation['accepted'],
                    'source' => 'existing',
                    'needsRouteCreation' => false,
                    'score' => $evaluation['score'],
                    'reason' => $evaluation['reason'],
                    'remainingCapacityKg' => $evaluation['remainingCapacityKg'],
                    'remainingPackageSlots' => $evaluation['remainingPackageSlots'],
                    'projectedLoadFactor' => $evaluation['projectedLoadFactor'],
                    'projectedPackageFactor' => $evaluation['projectedPackageFactor'],
                    'corridorDistanceKm' => $evaluation['corridorDistanceKm'],
                    'route' => $routePayload,
                    'driver' => $driverRecommendation,
                ];
            });

        $fallbackCandidate = self::fallbackRouteCandidate($shipment, $requestedDate, $driverPool);

        $scored = $existingCandidates
            ->when($fallbackCandidate !== null, fn (Collection $collection) => $collection->push($fallbackCandidate))
            ->filter(fn ($candidate) => $candidate['accepted'])
            ->sortByDesc('score')
            ->values();

        return $scored->first();
    }

    public static function materializeRecommendation(array $recommendation): array
    {
        if (empty($recommendation['needsRouteCreation']) || empty($recommendation['routeBlueprint'])) {
            return $recommendation;
        }

        $blueprint = $recommendation['routeBlueprint'];
        $nextSequence = (int) DB::table('rutas')->max('id') + 1;
        $routeCode = sprintf('AUTO-R-%04d', $nextSequence);
        $status = $blueprint['status'] ?? 'Preparacion';
        $statusId = DB::table('estado_ruta')->where('nombre', $status)->value('id');

        $routeId = DB::table('rutas')->insertGetId([
            'codigo' => $routeCode,
            'route_code' => $routeCode,
            'almacen_origen_id' => $blueprint['warehouseId'],
            'origen_almacen_id' => $blueprint['warehouseId'],
            'destino_almacen_id' => $blueprint['destinationWarehouseId'] ?: null,
            'warehouse_id' => $blueprint['warehouseId'],
            'distancia_km' => $blueprint['distanceKm'],
            'estimated_distance_km' => $blueprint['distanceKm'],
            'tiempo_estimado_min' => $blueprint['timeMinutes'],
            'estimated_time_minutes' => $blueprint['timeMinutes'],
            'vehicle_id' => $blueprint['vehicleId'] ?: null,
            'driver_id' => $blueprint['driverId'] ?: null,
            'scheduled_date' => $blueprint['scheduledDate'],
            'start_time' => null,
            'end_time' => null,
            'total_packages' => 0,
            'total_weight_kg' => 0,
            'actual_distance_km' => 0,
            'actual_time_minutes' => 0,
            'fuel_consumed_liters' => 0,
            'status' => $status,
            'estado' => $status,
            'estado_id' => $statusId,
            'optimization_score' => 0,
            'waypoints' => json_encode($blueprint['waypoints'] ?? [], JSON_UNESCAPED_SLASHES),
            'notes' => $blueprint['notes'] ?? 'Ruta generada automaticamente para balancear la operacion.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::syncRouteMetrics($routeId);

        $routeRecord = LogisticsSupport::routeBaseQuery()->where('rutas.id', $routeId)->first();

        $recommendation['route'] = LogisticsSupport::routePayload($routeRecord) ?: array_merge(
            $recommendation['route'] ?? [],
            [
                'id' => $routeId,
                'code' => $routeCode,
                'warehouseId' => $blueprint['warehouseId'],
                'driverId' => $blueprint['driverId'] ?: null,
                'vehicleId' => $blueprint['vehicleId'] ?: null,
            ]
        );
        $recommendation['source'] = 'created';
        $recommendation['needsRouteCreation'] = false;
        $recommendation['createdRouteId'] = $routeId;
        unset($recommendation['routeBlueprint']);

        return $recommendation;
    }

    public static function syncRouteMetrics(int $routeId): void
    {
        $route = LogisticsSupport::routeBaseQuery()->where('rutas.id', $routeId)->first();

        if (! $route) {
            return;
        }

        $assignedPackages = (int) ($route->assigned_packages ?? 0);
        $assignedWeight = (float) ($route->assigned_weight_kg ?? 0);
        $vehicleCapacity = (float) ($route->vehicle_capacity_kg ?? 0);
        $loadFactor = $vehicleCapacity > 0 ? min(($assignedWeight / $vehicleCapacity) * 100, 100) : 0;
        $timeMinutes = (int) ($route->actual_time_minutes ?: $route->tiempo_estimado_min ?: $route->estimated_time_minutes ?: 0);
        $resourceScore = ($route->vehicle_id ? 15 : 0) + ($route->driver_id ? 15 : 0);
        $loadScore = max(0, 40 - abs(75 - $loadFactor));
        $timeScore = max(0, 30 - min($timeMinutes / 8, 30));
        $optimizationScore = round(min(100, $loadScore + $timeScore + $resourceScore), 1);

        DB::table('rutas')->where('id', $routeId)->update([
            'total_packages' => $assignedPackages,
            'total_weight_kg' => $assignedWeight,
            'optimization_score' => $optimizationScore,
            'updated_at' => now(),
        ]);
    }

    private static function clientProfile(int $clientId): ?object
    {
        if ($clientId <= 0) {
            return null;
        }

        return DB::table('clientes')->where('id', $clientId)->first();
    }

    private static function destinationProfile(array $payload, ?object $recipient): array
    {
        $destination = [
            'id' => (int) ($payload['destinationAddressId'] ?? 0),
            'address' => trim((string) ($payload['destinationAddress'] ?? '')),
            'city' => trim((string) ($payload['destinationCity'] ?? '')),
            'state' => trim((string) ($payload['destinationState'] ?? '')),
            'postalCode' => trim((string) ($payload['destinationPostalCode'] ?? '')),
            'latitude' => self::numeric($payload['destinationLatitude'] ?? null),
            'longitude' => self::numeric($payload['destinationLongitude'] ?? null),
        ];

        if ($destination['id'] > 0) {
            $address = DB::table('cliente_direcciones')->where('id', $destination['id'])->first();

            if ($address) {
                $destination['address'] = $destination['address'] !== '' ? $destination['address'] : (string) ($address->address ?? '');
                $destination['city'] = $destination['city'] !== '' ? $destination['city'] : (string) ($address->city ?? '');
                $destination['state'] = $destination['state'] !== '' ? $destination['state'] : (string) ($address->state ?? '');
                $destination['postalCode'] = $destination['postalCode'] !== '' ? $destination['postalCode'] : (string) ($address->postal_code ?? '');
                $destination['latitude'] ??= self::numeric($address->latitude ?? null);
                $destination['longitude'] ??= self::numeric($address->longitude ?? null);
            }
        }

        if ($recipient) {
            $destination['address'] = $destination['address'] !== '' ? $destination['address'] : (string) ($recipient->default_address ?? '');
            $destination['latitude'] ??= self::numeric($recipient->latitude ?? null);
            $destination['longitude'] ??= self::numeric($recipient->longitude ?? null);
        }

        return $destination;
    }

    private static function warehouseProfile(int $warehouseId): ?object
    {
        if ($warehouseId <= 0) {
            return null;
        }

        return DB::table('almacenes')->where('id', $warehouseId)->first();
    }

    private static function warehouseAddress(object $warehouse): string
    {
        return trim(implode(', ', array_filter([
            $warehouse->address ?? null,
            $warehouse->city ?? null,
            $warehouse->state ?? null,
        ])));
    }

    private static function resolveWarehouseForShipment(array $shipment): ?object
    {
        $requestedDate = self::parseDate($shipment['scheduledDate'] ?? null) ?: now();
        $shipmentWeight = (float) ($shipment['weightKg'] ?? 0);
        $shipmentQuantity = max(1, (int) ($shipment['quantity'] ?? 1));

        $routeCounts = DB::table('rutas')
            ->selectRaw('COALESCE(warehouse_id, almacen_origen_id, origen_almacen_id) as warehouse_reference, COUNT(*) as total_routes')
            ->whereRaw('LOWER(COALESCE(status, estado, "")) not in (?, ?)', ['completada', 'cancelada'])
            ->whereDate('scheduled_date', $requestedDate->toDateString())
            ->groupBy(DB::raw('COALESCE(warehouse_id, almacen_origen_id, origen_almacen_id)'))
            ->pluck('total_routes', 'warehouse_reference');

        $vehicleCapacityByWarehouse = DB::table('vehiculos')
            ->selectRaw('warehouse_id, MAX(COALESCE(capacity_kg, capacidad_kg, 0)) as max_capacity_kg, MAX(COALESCE(capacity_packages, 0)) as max_capacity_packages')
            ->where(function ($query): void {
                $query->whereNull('activo')->orWhere('activo', 1);
            })
            ->groupBy('warehouse_id')
            ->get()
            ->keyBy('warehouse_id');

        return self::activeWarehouses()
            ->map(function ($warehouse) use ($shipment, $routeCounts, $vehicleCapacityByWarehouse, $shipmentWeight, $shipmentQuantity) {
                $senderDistance = self::distanceBetween(
                    self::numeric($warehouse->latitude ?? null),
                    self::numeric($warehouse->longitude ?? null),
                    $shipment['senderLatitude'] ?? null,
                    $shipment['senderLongitude'] ?? null,
                );
                $destinationDistance = self::distanceBetween(
                    self::numeric($warehouse->latitude ?? null),
                    self::numeric($warehouse->longitude ?? null),
                    $shipment['destinationLatitude'] ?? null,
                    $shipment['destinationLongitude'] ?? null,
                );
                $vehicleCapacity = $vehicleCapacityByWarehouse->get($warehouse->id);
                $maxCapacityKg = (float) ($vehicleCapacity->max_capacity_kg ?? 0);
                $maxCapacityPackages = (int) ($vehicleCapacity->max_capacity_packages ?? 0);
                $hasCapacity = ($maxCapacityKg <= 0 || $maxCapacityKg >= $shipmentWeight)
                    && ($maxCapacityPackages <= 0 || $maxCapacityPackages >= $shipmentQuantity);

                $score = 20;
                $score += $senderDistance !== null ? max(0, 48 - min($senderDistance, 60) * 1.4) : 0;
                $score += $destinationDistance !== null ? max(0, 28 - min($destinationDistance, 80) * 0.7) : 0;
                $score += min(18, ((int) ($routeCounts[$warehouse->id] ?? 0)) * 6);
                $score += $hasCapacity ? 14 : -18;

                return [
                    'score' => round($score, 1),
                    'warehouse' => $warehouse,
                ];
            })
            ->sortByDesc('score')
            ->first()['warehouse'] ?? null;
    }

    private static function activeWarehouses(): Collection
    {
        return DB::table('almacenes')
            ->where(function ($query): void {
                $query->whereNull('activo')->orWhere('activo', 1);
            })
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhere('status', 'active')
                    ->orWhere('status', 'activo');
            })
            ->get();
    }

    private static function candidateRoutes(int $warehouseId): Collection
    {
        return LogisticsSupport::routeBaseQuery()
            ->where(function ($query) use ($warehouseId): void {
                $query->where('rutas.almacen_origen_id', $warehouseId)
                    ->orWhere('rutas.warehouse_id', $warehouseId)
                    ->orWhere('rutas.origen_almacen_id', $warehouseId);
            })
            ->whereRaw('LOWER(COALESCE(rutas.status, rutas.estado, "")) not in (?, ?)', ['completada', 'cancelada'])
            ->get();
    }

    private static function candidateDrivers(Carbon $requestedDate): Collection
    {
        $shiftDate = $requestedDate->toDateString();

        return DB::table('conductores')
            ->leftJoin('estado_conductor', 'estado_conductor.id', '=', 'conductores.estado_id')
            ->leftJoin('vehiculos as current_vehicle', 'current_vehicle.id', '=', 'conductores.current_vehicle_id')
            ->leftJoin('turnos_conductor', function ($join) use ($shiftDate): void {
                $join->on('turnos_conductor.driver_id', '=', 'conductores.id')
                    ->where('turnos_conductor.shift_date', '=', $shiftDate);
            })
            ->select([
                'conductores.*',
                DB::raw('COALESCE(estado_conductor.nombre, conductores.status) as driver_status_name'),
                'turnos_conductor.shift_date',
                'turnos_conductor.start_time',
                'turnos_conductor.end_time',
                DB::raw("COALESCE(turnos_conductor.status, 'unscheduled') as shift_status_name"),
                DB::raw("COALESCE(turnos_conductor.estado, '') as shift_state_name"),
                DB::raw('COALESCE(turnos_conductor.successful_deliveries, 0) as deliveries_today'),
                DB::raw('COALESCE(turnos_conductor.failed_deliveries, 0) as failed_deliveries'),
                DB::raw('COALESCE(turnos_conductor.total_deliveries, 0) as total_deliveries'),
                DB::raw('COALESCE(current_vehicle.warehouse_id, 0) as current_vehicle_warehouse_id'),
                DB::raw('COALESCE(current_vehicle.placa, current_vehicle.plate) as current_vehicle_plate'),
            ])
            ->where(function ($query): void {
                $query->whereNull('conductores.activo')
                    ->orWhere('conductores.activo', 1);
            })
            ->get();
    }

    private static function evaluateCandidate(object $route, array $shipment, Carbon $requestedDate, ?array $driverRecommendation): array
    {
        $priority = LogisticsSupport::normalizePriority($shipment['priority'] ?? null) ?: 'standard';
        $serviceLevel = self::normalizeServiceLevel($shipment['serviceLevel'] ?? null);
        $statusKey = self::statusKey($route->estado_ruta_nombre ?? $route->status ?? $route->estado ?? '');
        $vehicleStatusKey = self::statusKey($route->vehicle_status_name ?? '');
        $routeDate = self::parseDate($route->scheduled_date ?? null) ?: $requestedDate->copy();
        $shipmentWeight = (float) ($shipment['weightKg'] ?? 0);
        $shipmentQuantity = max(1, (int) ($shipment['quantity'] ?? 1));
        $vehicleCapacity = (float) ($route->vehicle_capacity_kg ?? 0);
        $vehicleCapacityPackages = (int) ($route->vehicle_capacity_packages ?? 0);
        $assignedWeight = (float) ($route->assigned_weight_kg ?? 0);
        $assignedPackageUnits = (int) ($route->assigned_package_units ?? 0);
        $remainingCapacity = max($vehicleCapacity - $assignedWeight, 0);
        $remainingPackageSlots = max($vehicleCapacityPackages - $assignedPackageUnits, 0);
        $projectedLoadFactor = $vehicleCapacity > 0 ? min((($assignedWeight + $shipmentWeight) / $vehicleCapacity) * 100, 100) : 0;
        $projectedPackageFactor = $vehicleCapacityPackages > 0 ? min((($assignedPackageUnits + $shipmentQuantity) / $vehicleCapacityPackages) * 100, 100) : 0;
        $corridorDistanceKm = self::corridorDistanceKm($route, $shipment);
        $estimatedMissionDistanceKm = self::estimatedMissionDistanceKm($route, $shipment);
        $vehicleFuelRangeKm = (float) ($route->vehicle_fuel_range_km ?? 0);
        $routeProgress = self::routeProgressPercent($route, $requestedDate);

        if (str_contains($vehicleStatusKey, 'manten')) {
            return self::rejectedCandidate('Unidad en mantenimiento.', $remainingCapacity, $remainingPackageSlots, $corridorDistanceKm);
        }

        if (str_contains($statusKey, 'cancel') || str_contains($statusKey, 'complet')) {
            return self::rejectedCandidate('La ruta no esta disponible para nuevas asignaciones.', $remainingCapacity, $remainingPackageSlots, $corridorDistanceKm);
        }

        if ($shipmentWeight > 0 && $vehicleCapacity > 0 && $remainingCapacity < $shipmentWeight) {
            return self::rejectedCandidate('Capacidad de peso insuficiente para el envio.', $remainingCapacity, $remainingPackageSlots, $corridorDistanceKm);
        }

        if ($shipmentQuantity > 0 && $vehicleCapacityPackages > 0 && $remainingPackageSlots < $shipmentQuantity) {
            return self::rejectedCandidate('No hay espacio suficiente por cantidad de bultos.', $remainingCapacity, $remainingPackageSlots, $corridorDistanceKm);
        }

        if (! ($route->vehicle_id ?? null) && ($requestedDate->isToday() || in_array($priority, ['high', 'express'], true))) {
            return self::rejectedCandidate('La ruta no tiene unidad asignada para esta prioridad.', $remainingCapacity, $remainingPackageSlots, $corridorDistanceKm);
        }

        if (str_contains($statusKey, 'ejec') && ! $routeDate->isSameDay($requestedDate)) {
            return self::rejectedCandidate('La ruta en ejecucion pertenece a otra fecha operativa.', $remainingCapacity, $remainingPackageSlots, $corridorDistanceKm);
        }

        if ($routeDate->lt($requestedDate) && ! str_contains($statusKey, 'ejec')) {
            return self::rejectedCandidate('La ruta pertenece a un corte operativo anterior.', $remainingCapacity, $remainingPackageSlots, $corridorDistanceKm);
        }

        if ($priority === 'express' && ! $routeDate->isSameDay($requestedDate)) {
            return self::rejectedCandidate('La prioridad urgente requiere salida en la misma fecha operativa.', $remainingCapacity, $remainingPackageSlots, $corridorDistanceKm);
        }

        if ($priority === 'high' && $routeDate->gt($requestedDate->copy()->addDay())) {
            return self::rejectedCandidate('La ruta sale demasiado tarde para una prioridad alta.', $remainingCapacity, $remainingPackageSlots, $corridorDistanceKm);
        }

        if ($vehicleFuelRangeKm > 0 && $estimatedMissionDistanceKm > 0 && $vehicleFuelRangeKm < ($estimatedMissionDistanceKm * 1.1)) {
            return self::rejectedCandidate('La autonomia de la unidad es ajustada para la ruta estimada.', $remainingCapacity, $remainingPackageSlots, $corridorDistanceKm);
        }

        if (! $driverRecommendation && self::requiresAssignedDriver($requestedDate, $priority, $statusKey)) {
            return self::rejectedCandidate('No hay conductor compatible para esta ruta.', $remainingCapacity, $remainingPackageSlots, $corridorDistanceKm);
        }

        if (str_contains($statusKey, 'ejec') && $routeProgress >= 88 && ($corridorDistanceKm === null || $corridorDistanceKm > 8)) {
            return self::rejectedCandidate('La ruta ya esta muy avanzada para incorporar este destino.', $remainingCapacity, $remainingPackageSlots, $corridorDistanceKm);
        }

        $score = 24;
        $score += min(18, max(0, (float) ($route->optimization_score ?? 0) * 0.18));
        $score += self::dateAlignmentScore($requestedDate, $routeDate, $priority);
        $score += self::statusFitScore($statusKey, $priority);
        $score += self::corridorScore($corridorDistanceKm, $priority, $serviceLevel);
        $score += self::capacityFitScore($projectedLoadFactor, $projectedPackageFactor, $vehicleCapacity > 0, $vehicleCapacityPackages > 0);
        $score += ($route->vehicle_id ?? null) ? 8 : -4;
        $score += $driverRecommendation ? min(18, 6 + ($driverRecommendation['score'] * 0.22)) : -6;
        $score += self::fuelScore($vehicleFuelRangeKm, $estimatedMissionDistanceKm);
        $score += self::timeScore((int) ($route->tiempo_estimado_min ?? $route->estimated_time_minutes ?? 0), $priority, $serviceLevel);
        $score += self::serviceFitScore($serviceLevel, $priority, $statusKey, $driverRecommendation !== null);
        $score -= self::progressPenalty($routeProgress, $corridorDistanceKm, $priority);
        $score = round(min(100, max(0, $score)), 1);

        $reasonParts = [
            $routeDate ? 'ruta '.$routeDate->toDateString() : 'sin fecha operativa',
            $corridorDistanceKm !== null ? 'destino a '.round($corridorDistanceKm, 1).' km del corredor' : 'sin coordenadas del destino',
            $vehicleCapacity > 0 ? 'carga proyectada '.round($projectedLoadFactor, 1).'%' : 'sin capacidad de peso registrada',
            $vehicleCapacityPackages > 0 ? 'ocupacion por bultos '.round($projectedPackageFactor, 1).'%' : 'sin limite de bultos registrado',
            $driverRecommendation ? 'conductor sugerido '.$driverRecommendation['name'] : 'pendiente de conductor',
        ];

        if ($vehicleFuelRangeKm > 0) {
            $reasonParts[] = 'autonomia estimada '.round($vehicleFuelRangeKm, 1).' km';
        }

        return [
            'accepted' => true,
            'score' => $score,
            'reason' => ucfirst(implode(', ', $reasonParts)).'.',
            'remainingCapacityKg' => round($remainingCapacity, 1),
            'remainingPackageSlots' => $remainingPackageSlots,
            'projectedLoadFactor' => round($projectedLoadFactor, 1),
            'projectedPackageFactor' => round($projectedPackageFactor, 1),
            'corridorDistanceKm' => $corridorDistanceKm !== null ? round($corridorDistanceKm, 1) : null,
        ];
    }

    private static function fallbackRouteCandidate(array $shipment, Carbon $requestedDate, Collection $driverPool): ?array
    {
        $warehouseId = (int) ($shipment['originWarehouseId'] ?? 0);
        $warehouse = self::warehouseProfile($warehouseId);

        if (! $warehouse) {
            return null;
        }

        $distanceKm = self::distanceBetween(
            self::numeric($warehouse->latitude ?? null),
            self::numeric($warehouse->longitude ?? null),
            $shipment['destinationLatitude'] ?? null,
            $shipment['destinationLongitude'] ?? null,
        ) ?? self::averageWarehouseRouteDistance($warehouseId);
        $timeMinutes = self::estimateRouteTimeMinutes($distanceKm, LogisticsSupport::normalizePriority($shipment['priority'] ?? null) ?: 'standard');
        $vehicle = self::vehicleCandidatesForNewRoute($shipment, $requestedDate, $distanceKm)->first();

        if (! $vehicle) {
            return null;
        }

        $destinationWarehouse = self::nearestWarehouseToCoordinates($shipment['destinationLatitude'] ?? null, $shipment['destinationLongitude'] ?? null);
        $waypoints = array_values(array_filter([
            [
                'label' => (string) ($warehouse->nombre ?? $warehouse->code ?? $warehouse->codigo ?? 'Origen'),
                'lat' => self::numeric($warehouse->latitude ?? null),
                'lng' => self::numeric($warehouse->longitude ?? null),
            ],
            ($shipment['destinationLatitude'] ?? null) !== null && ($shipment['destinationLongitude'] ?? null) !== null
                ? [
                    'label' => $shipment['destinationAddress'] ?: 'Destino',
                    'lat' => (float) $shipment['destinationLatitude'],
                    'lng' => (float) $shipment['destinationLongitude'],
                ]
                : null,
        ]));

        $routeStub = (object) [
            'id' => 0,
            'codigo' => 'POR-CREAR',
            'route_code' => 'POR-CREAR',
            'almacen_origen_id' => $warehouseId,
            'origen_almacen_id' => $warehouseId,
            'warehouse_id' => $warehouseId,
            'warehouse_name' => (string) ($warehouse->nombre ?? $warehouse->code ?? $warehouse->codigo ?? 'Sin almacen'),
            'warehouse_latitude' => self::numeric($warehouse->latitude ?? null),
            'warehouse_longitude' => self::numeric($warehouse->longitude ?? null),
            'distancia_km' => $distanceKm,
            'estimated_distance_km' => $distanceKm,
            'tiempo_estimado_min' => $timeMinutes,
            'estimated_time_minutes' => $timeMinutes,
            'status' => 'Preparacion',
            'estado' => 'Preparacion',
            'vehicle_id' => $vehicle['id'],
            'vehicle_plate' => $vehicle['plate'],
            'vehicle_status_name' => $vehicle['status'],
            'vehicle_capacity_kg' => $vehicle['capacityKg'],
            'vehicle_capacity_packages' => $vehicle['capacityPackages'],
            'vehicle_current_fuel' => $vehicle['currentFuel'],
            'vehicle_fuel_consumption_km' => $vehicle['fuelConsumptionKm'],
            'vehicle_fuel_range_km' => $vehicle['fuelRangeKm'],
            'driver_id' => null,
            'scheduled_date' => $requestedDate->toDateString(),
            'optimization_score' => 0,
            'assigned_packages' => 0,
            'assigned_package_units' => 0,
            'assigned_weight_kg' => 0,
            'remaining_capacity_kg' => $vehicle['capacityKg'],
            'remaining_package_slots' => $vehicle['capacityPackages'],
            'waypoints' => json_encode($waypoints, JSON_UNESCAPED_SLASHES),
        ];

        $allowWithoutShift = $requestedDate->isFuture() && (LogisticsSupport::normalizePriority($shipment['priority'] ?? null) ?: 'standard') === 'standard';
        $driverRecommendation = self::resolveDriverCandidate($routeStub, $requestedDate, $driverPool, $allowWithoutShift);
        $evaluation = self::evaluateCandidate($routeStub, $shipment, $requestedDate, $driverRecommendation);

        if (! $evaluation['accepted']) {
            return null;
        }

        $priority = LogisticsSupport::normalizePriority($shipment['priority'] ?? null) ?: 'standard';
        $serviceLevel = self::normalizeServiceLevel($shipment['serviceLevel'] ?? null);
        $dedicatedRouteAdjustment = match ($priority) {
            'express' => 10,
            'high' => 5,
            default => 0,
        } + match ($serviceLevel) {
            'premium' => 6,
            'corporate' => 3,
            default => 0,
        } - match ($priority) {
            'express' => 2,
            'high' => 5,
            default => 10,
        };
        $score = round(min(100, max(0, $evaluation['score'] + $dedicatedRouteAdjustment)), 1);
        $routePayload = LogisticsSupport::routePayload($routeStub);

        if ($driverRecommendation) {
            $routePayload['driverId'] = $driverRecommendation['id'];
            $routePayload['driverName'] = $driverRecommendation['name'];
            $routePayload['driverStatus'] = $driverRecommendation['status'];
        }

        return [
            'accepted' => true,
            'source' => 'planned_new',
            'needsRouteCreation' => true,
            'score' => $score,
            'reason' => trim($evaluation['reason'].' Ruta nueva sugerida para balancear carga y nivel de servicio.'),
            'remainingCapacityKg' => $evaluation['remainingCapacityKg'],
            'remainingPackageSlots' => $evaluation['remainingPackageSlots'],
            'projectedLoadFactor' => $evaluation['projectedLoadFactor'],
            'projectedPackageFactor' => $evaluation['projectedPackageFactor'],
            'corridorDistanceKm' => 0.0,
            'route' => $routePayload,
            'driver' => $driverRecommendation,
            'routeBlueprint' => [
                'warehouseId' => $warehouseId,
                'warehouseName' => (string) ($warehouse->nombre ?? $warehouse->code ?? $warehouse->codigo ?? 'Sin almacen'),
                'destinationWarehouseId' => $destinationWarehouse ? (int) $destinationWarehouse->id : null,
                'scheduledDate' => $requestedDate->toDateString(),
                'distanceKm' => round($distanceKm, 1),
                'timeMinutes' => $timeMinutes,
                'status' => 'Preparacion',
                'vehicleId' => $vehicle['id'],
                'vehiclePlate' => $vehicle['plate'],
                'driverId' => $driverRecommendation['id'] ?? null,
                'driverName' => $driverRecommendation['name'] ?? null,
                'waypoints' => $waypoints,
                'notes' => 'Ruta creada automaticamente a partir del motor de asignacion para mantener SLA y capacidad operativa.',
            ],
        ];
    }

    private static function vehicleCandidatesForNewRoute(array $shipment, Carbon $requestedDate, float $distanceKm): Collection
    {
        $warehouseId = (int) ($shipment['originWarehouseId'] ?? 0);

        return DB::table('vehiculos')
            ->leftJoin('estado_vehiculo', 'estado_vehiculo.id', '=', 'vehiculos.estado_id')
            ->leftJoin('tipo_vehiculo', 'tipo_vehiculo.id', '=', 'vehiculos.tipo_id')
            ->select([
                'vehiculos.*',
                DB::raw('COALESCE(estado_vehiculo.nombre, vehiculos.status, vehiculos.estado) as vehicle_status_name'),
                DB::raw('COALESCE(tipo_vehiculo.nombre, vehiculos.type) as vehicle_type_name'),
            ])
            ->where('vehiculos.warehouse_id', $warehouseId)
            ->where(function ($query): void {
                $query->whereNull('vehiculos.activo')->orWhere('vehiculos.activo', 1);
            })
            ->get()
            ->map(fn ($vehicle) => self::vehicleCandidate($vehicle, $shipment, $requestedDate, $distanceKm))
            ->filter()
            ->sortByDesc('score')
            ->values();
    }

    private static function vehicleCandidate(object $vehicle, array $shipment, Carbon $requestedDate, float $distanceKm): ?array
    {
        $statusKey = self::statusKey($vehicle->vehicle_status_name ?? $vehicle->status ?? $vehicle->estado ?? '');

        if (str_contains($statusKey, 'manten')) {
            return null;
        }

        if (self::vehicleHasRouteConflict((int) $vehicle->id, $requestedDate)) {
            return null;
        }

        $capacityKg = (float) ($vehicle->capacity_kg ?? $vehicle->capacidad_kg ?? 0);
        $capacityPackages = (int) ($vehicle->capacity_packages ?? 0);
        $shipmentWeight = (float) ($shipment['weightKg'] ?? 0);
        $shipmentQuantity = max(1, (int) ($shipment['quantity'] ?? 1));

        if ($capacityKg > 0 && $capacityKg < $shipmentWeight) {
            return null;
        }

        if ($capacityPackages > 0 && $capacityPackages < $shipmentQuantity) {
            return null;
        }

        $fuelConsumption = (float) ($vehicle->fuel_consumption_km ?? $vehicle->consumo_km ?? 0);
        $currentFuel = (float) ($vehicle->current_fuel ?? 0);
        $fuelRangeKm = $fuelConsumption > 0 ? ($currentFuel / $fuelConsumption) : 0.0;
        $requiredDistance = max(12, $distanceKm * 1.35);

        if ($fuelRangeKm > 0 && $fuelRangeKm < ($requiredDistance * 1.05)) {
            return null;
        }

        $projectedLoadFactor = $capacityKg > 0 ? (($shipmentWeight / $capacityKg) * 100) : 0;
        $projectedPackageFactor = $capacityPackages > 0 ? (($shipmentQuantity / $capacityPackages) * 100) : 0;
        $typeKey = self::statusKey($vehicle->vehicle_type_name ?? $vehicle->type ?? '');
        $packageTypeKey = self::statusKey($shipment['packageType'] ?? '');

        $score = 22;
        $score += str_contains($statusKey, 'dispon') ? 18 : (str_contains($statusKey, 'operat') ? 12 : 0);
        $score += self::capacityFitScore($projectedLoadFactor, $projectedPackageFactor, $capacityKg > 0, $capacityPackages > 0);
        $score += $fuelRangeKm > 0 ? min(14, max(0, ($fuelRangeKm - $requiredDistance) / 10)) : 0;
        $score += max(0, 8 - ($fuelConsumption * 25));

        if ($shipmentWeight >= 900 && (str_contains($typeKey, 'camion') || str_contains($typeKey, 'caja'))) {
            $score += 6;
        }

        if (($packageTypeKey === 'documentacion' || $shipmentWeight <= 120) && str_contains($typeKey, 'van')) {
            $score += 4;
        }

        return [
            'id' => (int) $vehicle->id,
            'plate' => (string) ($vehicle->placa ?? $vehicle->plate ?? 'Sin placa'),
            'status' => (string) ($vehicle->vehicle_status_name ?? $vehicle->status ?? 'Disponible'),
            'score' => round($score, 1),
            'capacityKg' => $capacityKg,
            'capacityPackages' => $capacityPackages,
            'currentFuel' => $currentFuel,
            'fuelConsumptionKm' => $fuelConsumption,
            'fuelRangeKm' => round($fuelRangeKm, 1),
        ];
    }

    private static function resolveDriverCandidate(object $route, Carbon $requestedDate, Collection $driverPool, bool $allowWithoutShift = false): ?array
    {
        $currentDriverId = (int) ($route->driver_id ?? 0);

        if ($currentDriverId > 0) {
            $currentDriver = $driverPool->first(fn ($driver) => (int) $driver->id === $currentDriverId);

            if ($currentDriver && self::driverIsEligible($currentDriver, $route, $requestedDate, $allowWithoutShift)) {
                return self::driverRecommendation($currentDriver, $route, $requestedDate, true, $allowWithoutShift);
            }
        }

        return $driverPool
            ->map(function ($driver) use ($route, $requestedDate, $currentDriverId, $allowWithoutShift): ?array {
                if ($currentDriverId > 0 && (int) $driver->id === $currentDriverId) {
                    return null;
                }

                if (! self::driverIsEligible($driver, $route, $requestedDate, $allowWithoutShift)) {
                    return null;
                }

                return self::driverRecommendation($driver, $route, $requestedDate, false, $allowWithoutShift);
            })
            ->filter()
            ->sortByDesc('score')
            ->first();
    }

    private static function driverIsEligible(object $driver, object $route, Carbon $requestedDate, bool $allowWithoutShift = false): bool
    {
        if (isset($driver->activo) && (int) $driver->activo === 0) {
            return false;
        }

        $driverStatus = self::statusKey($driver->driver_status_name ?? $driver->status ?? '');

        if ($driverStatus && str_contains($driverStatus, 'fuera')) {
            return false;
        }

        $shiftState = self::statusKey($driver->shift_state_name ?? '');
        $shiftStatus = self::statusKey($driver->shift_status_name ?? '');

        if (($shiftState && str_contains($shiftState, 'cerr')) || in_array($shiftStatus, ['closed', 'completed'], true)) {
            return false;
        }

        $licenseExpiry = self::parseDate($driver->license_expiry ?? $driver->licencia_vence ?? null);

        if ($licenseExpiry && $licenseExpiry->lt($requestedDate->copy()->startOfDay())) {
            return false;
        }

        $hasShift = (string) ($driver->shift_date ?? '') === $requestedDate->toDateString();

        if (! $hasShift && ! $allowWithoutShift) {
            return false;
        }

        [$routeStart, $routeEnd] = self::routeWindow($route, $requestedDate);
        $shiftStart = $hasShift ? self::timeOnDate($requestedDate, $driver->start_time ?? null) : null;
        $shiftEnd = $hasShift ? self::timeOnDate($requestedDate, $driver->end_time ?? null) : null;

        if ($shiftStart && $shiftEnd && $routeEnd->gt($shiftEnd->copy()->addMinutes(30))) {
            return false;
        }

        return ! self::driverHasRouteConflict((int) $driver->id, (int) ($route->id ?? 0), $requestedDate);
    }

    private static function driverHasRouteConflict(int $driverId, int $routeId, Carbon $requestedDate): bool
    {
        return DB::table('rutas')
            ->where('driver_id', $driverId)
            ->when($routeId > 0, fn ($query) => $query->where('id', '<>', $routeId))
            ->whereRaw('LOWER(COALESCE(status, estado, "")) not in (?, ?)', ['completada', 'cancelada'])
            ->where(function ($query) use ($requestedDate): void {
                $query->whereDate('scheduled_date', $requestedDate->toDateString())
                    ->orWhereRaw('LOWER(COALESCE(status, estado, "")) like ?', ['%ejec%']);
            })
            ->exists();
    }

    private static function driverRecommendation(object $driver, object $route, Carbon $requestedDate, bool $preserveExisting, bool $allowWithoutShift = false): array
    {
        $status = (string) ($driver->driver_status_name ?? $driver->status ?? 'Disponible');
        $statusKey = self::statusKey($status);
        $routeVehicleId = (int) ($route->vehicle_id ?? 0);
        $routeWarehouseId = self::routeWarehouseId($route);
        $routeWarehouseLat = self::numeric($route->warehouse_latitude ?? null);
        $routeWarehouseLng = self::numeric($route->warehouse_longitude ?? null);
        $driverVehicleId = (int) ($driver->current_vehicle_id ?? 0);
        $driverWarehouseId = (int) ($driver->current_vehicle_warehouse_id ?? 0);
        $deliveriesToday = (int) ($driver->deliveries_today ?? 0);
        $failedDeliveries = (int) ($driver->failed_deliveries ?? 0);
        $shiftDate = (string) ($driver->shift_date ?? '');
        $hasShift = $shiftDate === $requestedDate->toDateString();
        $distanceToWarehouseKm = self::distanceBetween(
            self::numeric($driver->latitude ?? null),
            self::numeric($driver->longitude ?? null),
            $routeWarehouseLat,
            $routeWarehouseLng,
        );
        [$routeStart, $routeEnd] = self::routeWindow($route, $requestedDate);
        $shiftStart = $hasShift ? self::timeOnDate($requestedDate, $driver->start_time ?? null) : null;
        $shiftEnd = $hasShift ? self::timeOnDate($requestedDate, $driver->end_time ?? null) : null;
        $shiftSlackMinutes = ($shiftStart && $shiftEnd)
            ? min($shiftStart->diffInMinutes($routeStart, false), $shiftEnd->diffInMinutes($routeEnd, false))
            : null;

        $score = $preserveExisting ? 28 : 0;
        $score += str_contains($statusKey, 'dispon') ? 24 : (str_contains($statusKey, 'activo') ? 18 : (str_contains($statusKey, 'ruta') ? 8 : 0));
        $score += $routeVehicleId > 0 && $driverVehicleId === $routeVehicleId ? 26 : 0;
        $score += $routeWarehouseId > 0 && $driverWarehouseId === $routeWarehouseId ? 12 : 0;
        $score += $hasShift ? 16 : ($allowWithoutShift ? 4 : -6);
        $score += max(0, 12 - min(($deliveriesToday * 1.5) + ($failedDeliveries * 3), 12));
        $score += $distanceToWarehouseKm !== null ? max(0, 10 - min($distanceToWarehouseKm, 10)) : 0;

        if ($shiftSlackMinutes !== null) {
            $score += max(-8, min(12, $shiftSlackMinutes / 30));
        }

        $name = (string) ($driver->name ?? $driver->email ?? ('Conductor #'.$driver->id));
        $shiftWindow = trim(implode(' - ', array_filter([$driver->start_time ?? null, $driver->end_time ?? null])));
        $reasonParts = [
            $hasShift ? 'turno '.$shiftDate : 'sin turno cargado para la fecha',
            $routeVehicleId > 0 && $driverVehicleId === $routeVehicleId ? 'misma unidad operativa' : 'ajuste por disponibilidad general',
            $deliveriesToday > 0 ? $deliveriesToday.' entregas acumuladas' : 'sin carga acumulada',
        ];

        if ($distanceToWarehouseKm !== null) {
            $reasonParts[] = 'a '.round($distanceToWarehouseKm, 1).' km del punto de salida';
        }

        return [
            'id' => (int) $driver->id,
            'name' => $name,
            'status' => $status,
            'score' => round($score, 1),
            'reason' => ucfirst(implode(', ', $reasonParts)).'.',
            'shiftDate' => $hasShift ? $shiftDate : null,
            'shiftWindow' => $shiftWindow !== '' ? $shiftWindow : null,
            'deliveriesToday' => $deliveriesToday,
            'failedDeliveries' => $failedDeliveries,
        ];
    }

    private static function routeWarehouseId(object $route): int
    {
        return (int) ($route->warehouse_id ?? $route->almacen_origen_id ?? $route->origen_almacen_id ?? 0);
    }

    private static function requiresAssignedDriver(Carbon $requestedDate, string $priority, string $routeStatusKey): bool
    {
        if (str_contains($routeStatusKey, 'ejec')) {
            return true;
        }

        if ($requestedDate->isToday() || $requestedDate->isPast()) {
            return true;
        }

        return in_array($priority, ['high', 'express'], true);
    }

    private static function routeProgressPercent(object $route, Carbon $requestedDate): float
    {
        $statusKey = self::statusKey($route->estado_ruta_nombre ?? $route->status ?? $route->estado ?? '');

        if (! str_contains($statusKey, 'ejec')) {
            return 0.0;
        }

        $estimatedMinutes = max(1, (int) ($route->tiempo_estimado_min ?? $route->estimated_time_minutes ?? 0));
        $actualMinutes = (int) ($route->actual_time_minutes ?? 0);

        if ($actualMinutes > 0) {
            return min(100, ($actualMinutes / $estimatedMinutes) * 100);
        }

        [$routeStart] = self::routeWindow($route, $requestedDate);

        if ($routeStart) {
            $elapsed = max(0, now()->diffInMinutes($routeStart, false) * -1);

            return min(100, ($elapsed / $estimatedMinutes) * 100);
        }

        return 0.0;
    }

    private static function routeWindow(object $route, Carbon $requestedDate): array
    {
        $routeDate = self::parseDate($route->scheduled_date ?? null) ?: $requestedDate->copy();
        $start = self::timeOnDate($routeDate, $route->start_time ?? null);

        if (! $start) {
            $baseHour = $routeDate->isSameDay(now()) ? max((int) now()->format('H'), 8) : 8;
            $start = $routeDate->copy()->setTime($baseHour, 0, 0);
        }

        $duration = max(35, (int) ($route->tiempo_estimado_min ?? $route->estimated_time_minutes ?? 90));

        return [$start, $start->copy()->addMinutes($duration)];
    }

    private static function timeOnDate(Carbon $date, mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        try {
            if (preg_match('/^\\d{2}:\\d{2}(:\\d{2})?$/', $string) === 1) {
                $parts = array_map('intval', explode(':', $string));

                return $date->copy()->setTime($parts[0] ?? 0, $parts[1] ?? 0, $parts[2] ?? 0);
            }

            return Carbon::parse($string);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function corridorDistanceKm(object $route, array $shipment): ?float
    {
        $destinationLat = self::numeric($shipment['destinationLatitude'] ?? null);
        $destinationLng = self::numeric($shipment['destinationLongitude'] ?? null);

        if ($destinationLat === null || $destinationLng === null) {
            return null;
        }

        $points = collect(self::decodeWaypoints($route->waypoints ?? null));

        if ($points->isEmpty()) {
            $points = collect(array_filter([
                [
                    'lat' => self::numeric($route->warehouse_latitude ?? null),
                    'lng' => self::numeric($route->warehouse_longitude ?? null),
                ],
            ], fn ($item) => $item['lat'] !== null && $item['lng'] !== null));
        }

        if ($points->isEmpty()) {
            return null;
        }

        return $points
            ->map(fn ($point) => self::distanceBetween($point['lat'], $point['lng'], $destinationLat, $destinationLng))
            ->filter(fn ($distance) => $distance !== null)
            ->min();
    }

    private static function decodeWaypoints(mixed $value): array
    {
        if (! $value) {
            return [];
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(function ($point): ?array {
                $lat = self::numeric($point['lat'] ?? $point['latitude'] ?? null);
                $lng = self::numeric($point['lng'] ?? $point['longitude'] ?? null);

                if ($lat === null || $lng === null) {
                    return null;
                }

                return ['lat' => $lat, 'lng' => $lng];
            })
            ->filter()
            ->values()
            ->all();
    }

    private static function estimatedMissionDistanceKm(object $route, array $shipment): float
    {
        $plannedDistance = max(0, (float) ($route->estimated_distance_km ?? $route->distancia_km ?? 0));
        $directDistance = self::distanceBetween(
            self::numeric($route->warehouse_latitude ?? null),
            self::numeric($route->warehouse_longitude ?? null),
            $shipment['destinationLatitude'] ?? null,
            $shipment['destinationLongitude'] ?? null,
        );

        if ($directDistance === null) {
            return $plannedDistance;
        }

        return max($plannedDistance, round($directDistance * 1.2, 1));
    }

    private static function averageWarehouseRouteDistance(int $warehouseId): float
    {
        $average = DB::table('rutas')
            ->where(function ($query) use ($warehouseId): void {
                $query->where('warehouse_id', $warehouseId)
                    ->orWhere('almacen_origen_id', $warehouseId)
                    ->orWhere('origen_almacen_id', $warehouseId);
            })
            ->whereRaw('LOWER(COALESCE(status, estado, "")) not in (?, ?)', ['completada', 'cancelada'])
            ->value(DB::raw('AVG(COALESCE(estimated_distance_km, distancia_km, 0))'));

        return max(18.0, (float) ($average ?? 0));
    }

    private static function nearestWarehouseToCoordinates(int|float|null $latitude, int|float|null $longitude): ?object
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return self::activeWarehouses()
            ->map(function ($warehouse) use ($latitude, $longitude) {
                return [
                    'distance' => self::distanceBetween(
                        self::numeric($warehouse->latitude ?? null),
                        self::numeric($warehouse->longitude ?? null),
                        $latitude,
                        $longitude,
                    ),
                    'warehouse' => $warehouse,
                ];
            })
            ->filter(fn ($item) => $item['distance'] !== null)
            ->sortBy('distance')
            ->first()['warehouse'] ?? null;
    }

    private static function estimateRouteTimeMinutes(float $distanceKm, string $priority): int
    {
        $speedKmH = $distanceKm > 60 ? 55 : 32;
        $buffer = $distanceKm > 60 ? 35 : 22;
        $priorityAdjustment = match ($priority) {
            'express' => -8,
            'high' => -4,
            default => 0,
        };

        return (int) round(max(35, (($distanceKm / max($speedKmH, 1)) * 60) + $buffer + $priorityAdjustment));
    }

    private static function vehicleHasRouteConflict(int $vehicleId, Carbon $requestedDate): bool
    {
        return DB::table('rutas')
            ->where('vehicle_id', $vehicleId)
            ->whereRaw('LOWER(COALESCE(status, estado, "")) not in (?, ?)', ['completada', 'cancelada'])
            ->where(function ($query) use ($requestedDate): void {
                $query->whereDate('scheduled_date', $requestedDate->toDateString())
                    ->orWhereRaw('LOWER(COALESCE(status, estado, "")) like ?', ['%ejec%']);
            })
            ->exists();
    }

    private static function dateAlignmentScore(Carbon $requestedDate, Carbon $routeDate, string $priority): float
    {
        if ($routeDate->isSameDay($requestedDate)) {
            return 18;
        }

        if ($routeDate->isSameDay($requestedDate->copy()->addDay())) {
            return match ($priority) {
                'express' => -8,
                'high' => 3,
                default => 8,
            };
        }

        if ($routeDate->isSameWeek($requestedDate)) {
            return $priority === 'standard' ? 2 : -6;
        }

        return -10;
    }

    private static function statusFitScore(string $statusKey, string $priority): float
    {
        if (str_contains($statusKey, 'prepar')) {
            return $priority === 'express' ? 14 : 16;
        }

        if (str_contains($statusKey, 'ejec')) {
            return $priority === 'express' ? 10 : 12;
        }

        return 6;
    }

    private static function corridorScore(?float $corridorDistanceKm, string $priority, string $serviceLevel): float
    {
        if ($corridorDistanceKm === null) {
            return 2;
        }

        $score = match (true) {
            $corridorDistanceKm <= 3 => 18,
            $corridorDistanceKm <= 8 => 14,
            $corridorDistanceKm <= 15 => 9,
            $corridorDistanceKm <= 25 => 2,
            default => -12,
        };

        if (($priority === 'express' || $serviceLevel === 'premium') && $corridorDistanceKm > 10) {
            $score -= 6;
        }

        return $score;
    }

    private static function capacityFitScore(float $projectedLoadFactor, float $projectedPackageFactor, bool $hasWeightCapacity, bool $hasPackageCapacity): float
    {
        $loadScore = 0;
        $packageScore = 0;

        if ($hasWeightCapacity) {
            $loadScore = match (true) {
                $projectedLoadFactor >= 55 && $projectedLoadFactor <= 85 => 12,
                $projectedLoadFactor >= 35 && $projectedLoadFactor <= 95 => 7,
                default => -4,
            };
        }

        if ($hasPackageCapacity) {
            $packageScore = match (true) {
                $projectedPackageFactor >= 45 && $projectedPackageFactor <= 85 => 8,
                $projectedPackageFactor > 0 && $projectedPackageFactor <= 95 => 4,
                default => -2,
            };
        }

        return $loadScore + $packageScore;
    }

    private static function fuelScore(float $fuelRangeKm, float $missionDistanceKm): float
    {
        if ($fuelRangeKm <= 0 || $missionDistanceKm <= 0) {
            return 0;
        }

        return min(12, max(0, ($fuelRangeKm - $missionDistanceKm) / 10));
    }

    private static function timeScore(int $timeMinutes, string $priority, string $serviceLevel): float
    {
        $idealMinutes = match ($priority) {
            'express' => 90,
            'high' => 130,
            default => $serviceLevel === 'premium' ? 140 : 180,
        };

        return max(-4, 12 - max(0, ($timeMinutes - $idealMinutes) / 15));
    }

    private static function serviceFitScore(string $serviceLevel, string $priority, string $statusKey, bool $hasDriver): float
    {
        $score = 0;

        if ($serviceLevel === 'premium') {
            $score += $hasDriver ? 7 : -4;
        } elseif ($serviceLevel === 'corporate') {
            $score += 3;
        }

        if ($priority === 'express') {
            $score += 6;
        } elseif ($priority === 'high') {
            $score += 3;
        }

        if (str_contains($statusKey, 'prepar') || str_contains($statusKey, 'ejec')) {
            $score += 3;
        }

        return $score;
    }

    private static function progressPenalty(float $progress, ?float $corridorDistanceKm, string $priority): float
    {
        if ($progress < 55) {
            return 0;
        }

        $basePenalty = max(0, ($progress - 50) / 8);

        if ($corridorDistanceKm !== null && $corridorDistanceKm <= 5) {
            return $basePenalty / 2;
        }

        $multiplier = match ($priority) {
            'express' => 1.9,
            'high' => 1.4,
            default => 1.0,
        };

        return min(18, $basePenalty + (($corridorDistanceKm ?? 10) * 0.4 * $multiplier));
    }

    private static function rejectedCandidate(string $reason, float $remainingCapacity, int $remainingPackageSlots, ?float $corridorDistanceKm = null): array
    {
        return [
            'accepted' => false,
            'score' => 0,
            'reason' => $reason,
            'remainingCapacityKg' => round($remainingCapacity, 1),
            'remainingPackageSlots' => $remainingPackageSlots,
            'projectedLoadFactor' => 0,
            'projectedPackageFactor' => 0,
            'corridorDistanceKm' => $corridorDistanceKm !== null ? round($corridorDistanceKm, 1) : null,
        ];
    }

    private static function parseDate(mixed $value): ?Carbon
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

    private static function statusKey(?string $value): string
    {
        return strtolower(trim(Str::ascii((string) $value)));
    }

    private static function normalizeServiceLevel(?string $value): string
    {
        $normalized = self::statusKey($value);

        return match ($normalized) {
            'premium', 'vip' => 'premium',
            'corporativo', 'corporate' => 'corporate',
            default => 'standard',
        };
    }

    private static function numeric(mixed $value): int|float|null
    {
        return is_numeric($value) ? $value + 0 : null;
    }

    private static function distanceBetween(int|float|null $lat1, int|float|null $lng1, int|float|null $lat2, int|float|null $lng2): ?float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }

        $earthRadiusKm = 6371;
        $deltaLat = deg2rad((float) $lat2 - (float) $lat1);
        $deltaLng = deg2rad((float) $lng2 - (float) $lng1);
        $originLat = deg2rad((float) $lat1);
        $destinationLat = deg2rad((float) $lat2);
        $a = sin($deltaLat / 2) ** 2
            + cos($originLat) * cos($destinationLat) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
