<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

class LogisticsSupport
{
    public static function apiUser(Request $request): object
    {
        return $request->attributes->get('apiUser');
    }

    public static function userDisplayName(object $user): string
    {
        $fullName = trim(implode(' ', array_filter([
            $user->nombre ?? null,
            $user->apellido_paterno ?? null,
        ])));

        if ($fullName !== '') {
            return $fullName;
        }

        $legacy = trim((string) ($user->nombres ?? '').' '.(string) ($user->apellidos ?? ''));

        return $legacy !== '' ? $legacy : (string) $user->email;
    }

    public static function userPayload(object $user): array
    {
        return [
            'id' => (int) $user->id,
            'email' => $user->email,
            'role' => $user->role_name ?? 'operator',
            'name' => self::userDisplayName($user),
            'active' => (bool) ($user->activo ?? 1),
        ];
    }

    public static function roleName(?object $user): string
    {
        return strtolower((string) ($user->role_name ?? $user->role ?? 'operator'));
    }

    public static function shipmentBaseQueryFor(?Request $request = null): Builder
    {
        $query = self::shipmentBaseQuery();

        if (! $request) {
            return $query;
        }

        return self::applyShipmentVisibility($query, self::apiUser($request));
    }

    public static function routeBaseQueryFor(?Request $request = null): Builder
    {
        $query = self::routeBaseQuery();

        if (! $request) {
            return $query;
        }

        return self::applyRouteVisibility($query, self::apiUser($request));
    }

    public static function evidenceBaseQueryFor(?Request $request = null): Builder
    {
        $query = self::evidenceBaseQuery();

        if (! $request) {
            return $query;
        }

        $user = self::apiUser($request);
        $role = self::roleName($user);
        $personaId = (int) ($user->persona_id ?? 0);

        if ($role === 'driver') {
            if (! $personaId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where('conductores.persona_id', $personaId);
        }

        if ($role === 'customer') {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function applyShipmentVisibility(Builder $query, ?object $user): Builder
    {
        $role = self::roleName($user);
        $personaId = (int) ($user->persona_id ?? 0);

        if ($role === 'customer') {
            if (! $personaId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where(function ($visibility) use ($personaId): void {
                $visibility->where('client_owner.persona_id', $personaId)
                    ->orWhere('sender_client.persona_id', $personaId)
                    ->orWhere('recipient_client.persona_id', $personaId);
            });
        }

        if ($role === 'driver') {
            if (! $personaId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where('conductores.persona_id', $personaId);
        }

        return $query;
    }

    public static function applyRouteVisibility(Builder $query, ?object $user): Builder
    {
        $role = self::roleName($user);
        $personaId = (int) ($user->persona_id ?? 0);

        if ($role === 'driver') {
            if (! $personaId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where(function ($visibility) use ($personaId): void {
                $visibility->where('route_driver.persona_id', $personaId)
                    ->orWhere('persona_driver.persona_id', $personaId)
                    ->orWhere('rutas.driver_id', $personaId);
            });
        }

        return $query;
    }

    public static function roleMap(): array
    {
        return [
            'admin' => 1,
            'operator' => 2,
            'supervisor' => 3,
            'dispatcher' => 4,
            'driver' => 5,
            'customer' => 6,
        ];
    }

    public static function roleIdFor(?string $role): ?int
    {
        if (! $role) {
            return null;
        }

        $named = DB::table('roles')->where('nombre', $role)->value('id');

        return $named ? (int) $named : (self::roleMap()[$role] ?? null);
    }

    public static function routePayload(?object $route): ?array
    {
        if (! $route) {
            return null;
        }

        $routeCode = self::pickString($route, ['codigo', 'route_code']) ?: sprintf('RUTA-%04d', $route->id);
        $warehouseName = self::pickString($route, ['warehouse_name']) ?: 'Sin almacen';
        $routeStatus = self::pickString($route, ['estado_ruta_nombre', 'status', 'estado']) ?: 'Planeada';
        $vehiclePlate = self::pickString($route, ['vehicle_plate']);
        $vehicleStatus = self::pickString($route, ['vehicle_status_name']) ?: 'Sin unidad';
        $driverName = self::pickString($route, ['driver_name']);
        $driverStatus = self::pickString($route, ['driver_status_name']) ?: 'Sin conductor';

        return [
            'id' => (int) $route->id,
            'code' => $routeCode,
            'warehouseId' => self::pickInt($route, ['almacen_origen_id', 'origen_almacen_id', 'warehouse_id']),
            'warehouseName' => $warehouseName,
            'distanceKm' => (float) ($route->distancia_km ?? $route->estimated_distance_km ?? 0),
            'timeMinutes' => (int) ($route->tiempo_estimado_min ?? $route->estimated_time_minutes ?? 0),
            'status' => $routeStatus,
            'vehicleId' => self::pickInt($route, ['vehicle_id']),
            'vehiclePlate' => $vehiclePlate,
            'vehicleStatus' => $vehicleStatus,
            'driverId' => self::pickInt($route, ['resolved_driver_id', 'driver_id']),
            'driverName' => $driverName,
            'driverStatus' => $driverStatus,
            'scheduledDate' => self::pickString($route, ['scheduled_date']),
            'optimizationScore' => (float) ($route->optimization_score ?? 0),
            'startTime' => self::pickString($route, ['start_time']),
            'endTime' => self::pickString($route, ['end_time']),
            'actualTimeMinutes' => (int) ($route->actual_time_minutes ?? 0),
            'fuelConsumedLiters' => (float) ($route->fuel_consumed_liters ?? 0),
            'totalPackages' => (int) ($route->total_packages ?? 0),
            'totalWeightKg' => (float) ($route->total_weight_kg ?? 0),
            'assignedPackages' => (int) ($route->assigned_packages ?? $route->total_packages ?? 0),
            'assignedWeightKg' => (float) ($route->assigned_weight_kg ?? $route->total_weight_kg ?? 0),
            'assignedPackageUnits' => (int) ($route->assigned_package_units ?? 0),
            'vehicleCapacityKg' => (float) ($route->vehicle_capacity_kg ?? 0),
            'vehicleCapacityPackages' => (int) ($route->vehicle_capacity_packages ?? 0),
            'remainingCapacityKg' => (float) ($route->remaining_capacity_kg ?? 0),
            'remainingPackageSlots' => (int) ($route->remaining_package_slots ?? 0),
            'loadFactor' => (float) ($route->load_factor ?? 0),
            'vehicleFuelRangeKm' => (float) ($route->vehicle_fuel_range_km ?? 0),
            'createdAt' => $route->created_at ?? null,
        ];
    }

    public static function shipmentPayload(?object $shipment): ?array
    {
        if (! $shipment) {
            return null;
        }

        $routeCode = self::pickString($shipment, ['route_code', 'route_alias']) ?: 'Pendiente';
        $status = self::pickString($shipment, ['estado_paquete_nombre', 'status', 'estado']) ?: 'Pendiente';
        $tracking = self::pickString($shipment, ['codigo_tracking', 'tracking_code', 'codigo_rastreo']);
        $senderName = self::pickString($shipment, ['sender_name', 'cliente_nombre']) ?: 'Sin remitente';
        $recipientName = self::pickString($shipment, ['recipient_name']) ?: 'Sin destinatario';
        $warehouseName = self::pickString($shipment, ['warehouse_name']) ?: 'Asignacion automatica';
        $routeStatus = self::pickString($shipment, ['route_status']) ?: 'Sin ruta';
        $vehiclePlate = self::pickString($shipment, ['vehicle_plate']) ?: 'Pendiente';
        $driverName = self::pickString($shipment, ['driver_name']) ?: 'Pendiente';
        $packageType = self::pickString($shipment, ['tipo_paquete_nombre', 'package_type']) ?: 'Carga general';
        $scheduledDate = self::pickString($shipment, ['scheduled_date'])
            ?: ($shipment->fecha_estimada_entrega ?? null ? substr((string) $shipment->fecha_estimada_entrega, 0, 10) : null);
        $originAddress = self::pickString($shipment, ['origin_address', 'origen_referencia']) ?: '';
        $destinationAddress = self::pickString($shipment, ['recipient_address', 'destino_referencia']) ?: '';
        $destinationCity = self::pickString($shipment, ['recipient_city', 'destino_ciudad']) ?: '';
        $destinationState = self::pickString($shipment, ['recipient_state', 'destino_estado']) ?: '';
        $destinationPostalCode = self::pickString($shipment, ['recipient_postal_code', 'destino_codigo_postal']) ?: '';
        $description = self::pickString($shipment, ['description', 'descripcion']) ?: '';

        return [
            'id' => (int) $shipment->id,
            'tracking' => $tracking,
            'senderId' => self::pickInt($shipment, ['sender_id', 'cliente_id']),
            'recipientId' => self::pickInt($shipment, ['recipient_id']),
            'senderName' => $senderName,
            'recipientName' => $recipientName,
            'customerName' => $senderName !== 'Sin remitente' ? $senderName : 'Sin cliente',
            'originWarehouseId' => self::pickInt($shipment, ['origin_warehouse_id']),
            'warehouseName' => $warehouseName,
            'routeId' => self::pickInt($shipment, ['route_id']),
            'routeCode' => $routeCode,
            'routeStatus' => $routeStatus,
            'vehicleId' => self::pickInt($shipment, ['vehicle_id']),
            'vehiclePlate' => $vehiclePlate,
            'driverId' => self::pickInt($shipment, ['driver_id']),
            'driverName' => $driverName,
            'packageType' => $packageType,
            'initialStatus' => $status,
            'status' => $status,
            'priority' => self::normalizePriority(self::pickString($shipment, ['priority'])) ?: 'standard',
            'weightKg' => (float) self::pickNumeric($shipment, ['weight_kg', 'peso_kg', 'peso']),
            'volumeM3' => (float) self::pickNumeric($shipment, ['volume_m3', 'volumen_m3', 'volumen']),
            'quantity' => (int) ($shipment->quantity ?? 1),
            'scheduledDate' => $scheduledDate,
            'originAddress' => $originAddress,
            'destinationAddressId' => self::pickInt($shipment, ['recipient_address_id', 'direccion_destino_id', 'destino_direccion_id']),
            'destinationAddress' => $destinationAddress,
            'destinationCity' => $destinationCity,
            'destinationState' => $destinationState,
            'destinationPostalCode' => $destinationPostalCode,
            'destinationLatitude' => self::pickNumeric($shipment, ['recipient_latitude']),
            'destinationLongitude' => self::pickNumeric($shipment, ['recipient_longitude']),
            'description' => $description,
            'declaredValue' => (float) ($shipment->declared_value ?? 0),
            'createdAt' => $shipment->created_at ?? null,
            'assignedAt' => $shipment->assigned_at ?? null,
            'deliveryTime' => $shipment->delivery_time ?? null,
            'promisedDate' => $shipment->promised_date ?? null,
            'attempts' => (int) ($shipment->attempts ?? 0),
            'latestAssignment' => [
                'route' => $routeCode,
                'vehicle' => $vehiclePlate,
                'driver' => $driverName,
            ],
        ];
    }

    public static function shipmentBaseQuery()
    {
        return DB::table('paquetes')
            ->leftJoin('tipo_paquete', 'tipo_paquete.id', '=', 'paquetes.tipo_id')
            ->leftJoin('estado_paquete', 'estado_paquete.id', '=', 'paquetes.estado_id')
            ->leftJoin('clientes as sender_client', 'sender_client.id', '=', 'paquetes.sender_id')
            ->leftJoin('clientes as client_owner', 'client_owner.id', '=', 'paquetes.cliente_id')
            ->leftJoin('clientes as recipient_client', 'recipient_client.id', '=', 'paquetes.recipient_id')
            ->leftJoin('almacenes', 'almacenes.id', '=', 'paquetes.origin_warehouse_id')
            ->leftJoin('direcciones as origin_address', 'origin_address.id', '=', 'paquetes.direccion_origen_id')
            ->leftJoin('direcciones as destination_address', 'destination_address.id', '=', 'paquetes.direccion_destino_id')
            ->leftJoinSub(
                DB::table('asignaciones')
                    ->selectRaw('MAX(id) as latest_assignment_id, package_id')
                    ->groupBy('package_id'),
                'latest_assignments',
                fn ($join) => $join->on('latest_assignments.package_id', '=', 'paquetes.id')
            )
            ->leftJoin('asignaciones', 'asignaciones.id', '=', 'latest_assignments.latest_assignment_id')
            ->leftJoin('rutas', function ($join): void {
                $join->on('rutas.id', '=', 'asignaciones.ruta_id')
                    ->orOn('rutas.id', '=', 'asignaciones.route_id');
            })
            ->leftJoin('vehiculos', function ($join): void {
                $join->on('vehiculos.id', '=', 'asignaciones.vehiculo_id')
                    ->orOn('vehiculos.id', '=', 'asignaciones.vehicle_id');
            })
            ->leftJoin('conductores', function ($join): void {
                $join->on('conductores.id', '=', 'asignaciones.conductor_id')
                    ->orOn('conductores.id', '=', 'asignaciones.driver_id');
            })
            ->leftJoin('personas as sender_person', 'sender_person.id', '=', 'sender_client.persona_id')
            ->leftJoin('personas as recipient_person', 'recipient_person.id', '=', 'recipient_client.persona_id')
            ->leftJoin('personas as driver_person', 'driver_person.id', '=', 'conductores.persona_id')
            ->select([
                'paquetes.*',
                DB::raw('COALESCE(sender_client.name, CONCAT_WS(" ", sender_person.nombre, sender_person.apellido_paterno), CONCAT_WS(" ", sender_person.nombres, sender_person.apellidos), client_owner.name) as sender_name'),
                DB::raw('COALESCE(client_owner.name, sender_client.name, CONCAT_WS(" ", sender_person.nombre, sender_person.apellido_paterno)) as cliente_nombre'),
                DB::raw('COALESCE(recipient_client.name, CONCAT_WS(" ", recipient_person.nombre, recipient_person.apellido_paterno), CONCAT_WS(" ", recipient_person.nombres, recipient_person.apellidos)) as recipient_name'),
                DB::raw('COALESCE(almacenes.nombre, almacenes.code, almacenes.codigo) as warehouse_name'),
                DB::raw('COALESCE(origin_address.referencia, CONCAT_WS(" ", origin_address.calle, origin_address.numero, origin_address.colonia)) as origin_address'),
                DB::raw('COALESCE(destination_address.referencia, CONCAT_WS(" ", destination_address.calle, destination_address.numero, destination_address.colonia)) as destino_referencia'),
                DB::raw('destination_address.ciudad as destino_ciudad'),
                DB::raw('destination_address.estado as destino_estado'),
                DB::raw('destination_address.codigo_postal as destino_codigo_postal'),
                DB::raw('COALESCE(tipo_paquete.nombre, paquetes.package_type) as tipo_paquete_nombre'),
                DB::raw('COALESCE(estado_paquete.nombre, paquetes.status, paquetes.estado) as estado_paquete_nombre'),
                DB::raw('COALESCE(rutas.codigo, rutas.route_code) as route_code'),
                DB::raw('COALESCE(rutas.status, rutas.estado) as route_status'),
                DB::raw('rutas.id as route_id'),
                DB::raw('vehiculos.id as vehicle_id'),
                DB::raw('COALESCE(vehiculos.placa, vehiculos.plate) as vehicle_plate'),
                DB::raw('conductores.id as driver_id'),
                DB::raw('COALESCE(conductores.name, CONCAT_WS(" ", driver_person.nombre, driver_person.apellido_paterno), CONCAT_WS(" ", driver_person.nombres, driver_person.apellidos)) as driver_name'),
            ]);
    }

    public static function routeBaseQuery()
    {
        $assignments = DB::table('asignaciones')
            ->leftJoin('paquetes', 'paquetes.id', '=', 'asignaciones.package_id')
            ->selectRaw('COALESCE(asignaciones.ruta_id, asignaciones.route_id) as route_reference')
            ->selectRaw('COUNT(DISTINCT asignaciones.package_id) as assigned_packages')
            ->selectRaw('COALESCE(SUM(COALESCE(paquetes.quantity, 1)), 0) as assigned_package_units')
            ->selectRaw('COALESCE(SUM(COALESCE(paquetes.weight_kg, paquetes.peso_kg, paquetes.peso, 0)), 0) as assigned_weight_kg')
            ->groupBy(DB::raw('COALESCE(asignaciones.ruta_id, asignaciones.route_id)'));

        return DB::table('rutas')
            ->leftJoinSub($assignments, 'route_assignments', fn ($join) => $join->on('route_assignments.route_reference', '=', 'rutas.id'))
            ->leftJoin('almacenes', function ($join): void {
                $join->on('almacenes.id', '=', 'rutas.almacen_origen_id')
                    ->orOn('almacenes.id', '=', 'rutas.warehouse_id')
                    ->orOn('almacenes.id', '=', 'rutas.origen_almacen_id');
            })
            ->leftJoin('estado_ruta', 'estado_ruta.id', '=', 'rutas.estado_id')
            ->leftJoin('vehiculos', 'vehiculos.id', '=', 'rutas.vehicle_id')
            ->leftJoin('estado_vehiculo', 'estado_vehiculo.id', '=', 'vehiculos.estado_id')
            ->leftJoin('conductores as route_driver', 'route_driver.id', '=', 'rutas.driver_id')
            ->leftJoin('conductores as persona_driver', 'persona_driver.persona_id', '=', 'rutas.driver_id')
            ->leftJoin('estado_conductor as route_driver_status', 'route_driver_status.id', '=', 'route_driver.estado_id')
            ->leftJoin('estado_conductor as persona_driver_status', 'persona_driver_status.id', '=', 'persona_driver.estado_id')
            ->leftJoin('personas as route_driver_person', 'route_driver_person.id', '=', 'route_driver.persona_id')
            ->leftJoin('personas as persona_driver_person', 'persona_driver_person.id', '=', 'persona_driver.persona_id')
            ->select([
                'rutas.*',
                DB::raw('COALESCE(almacenes.nombre, almacenes.code, almacenes.codigo) as warehouse_name'),
                DB::raw('COALESCE(almacenes.latitude, 0) as warehouse_latitude'),
                DB::raw('COALESCE(almacenes.longitude, 0) as warehouse_longitude'),
                DB::raw('COALESCE(estado_ruta.nombre, rutas.status, rutas.estado) as estado_ruta_nombre'),
                DB::raw('COALESCE(vehiculos.placa, vehiculos.plate) as vehicle_plate'),
                DB::raw('COALESCE(estado_vehiculo.nombre, vehiculos.status, vehiculos.estado) as vehicle_status_name'),
                DB::raw('COALESCE(route_driver.id, persona_driver.id) as resolved_driver_id'),
                DB::raw('COALESCE(route_driver.name, persona_driver.name, CONCAT_WS(" ", route_driver_person.nombre, route_driver_person.apellido_paterno), CONCAT_WS(" ", route_driver_person.nombres, route_driver_person.apellidos), CONCAT_WS(" ", persona_driver_person.nombre, persona_driver_person.apellido_paterno), CONCAT_WS(" ", persona_driver_person.nombres, persona_driver_person.apellidos)) as driver_name'),
                DB::raw('COALESCE(route_driver_status.nombre, route_driver.status, persona_driver_status.nombre, persona_driver.status) as driver_status_name'),
                DB::raw('COALESCE(route_assignments.assigned_packages, 0) as assigned_packages'),
                DB::raw('COALESCE(route_assignments.assigned_package_units, 0) as assigned_package_units'),
                DB::raw('COALESCE(route_assignments.assigned_weight_kg, 0) as assigned_weight_kg'),
                DB::raw('COALESCE(vehiculos.capacity_kg, vehiculos.capacidad_kg, 0) as vehicle_capacity_kg'),
                DB::raw('COALESCE(vehiculos.capacity_packages, 0) as vehicle_capacity_packages'),
                DB::raw('COALESCE(vehiculos.current_fuel, 0) as vehicle_current_fuel'),
                DB::raw('COALESCE(vehiculos.fuel_capacity, 0) as vehicle_fuel_capacity'),
                DB::raw('COALESCE(vehiculos.fuel_consumption_km, vehiculos.consumo_km, 0) as vehicle_fuel_consumption_km'),
                DB::raw('CASE WHEN COALESCE(vehiculos.fuel_consumption_km, vehiculos.consumo_km, 0) > 0 THEN ROUND(COALESCE(vehiculos.current_fuel, 0) / COALESCE(vehiculos.fuel_consumption_km, vehiculos.consumo_km, 1), 1) ELSE 0 END as vehicle_fuel_range_km'),
                DB::raw('GREATEST(COALESCE(vehiculos.capacity_kg, vehiculos.capacidad_kg, 0) - COALESCE(route_assignments.assigned_weight_kg, 0), 0) as remaining_capacity_kg'),
                DB::raw('GREATEST(COALESCE(vehiculos.capacity_packages, 0) - COALESCE(route_assignments.assigned_package_units, 0), 0) as remaining_package_slots'),
                DB::raw('CASE WHEN COALESCE(vehiculos.capacity_kg, vehiculos.capacidad_kg, 0) > 0 THEN ROUND((COALESCE(route_assignments.assigned_weight_kg, 0) / COALESCE(vehiculos.capacity_kg, vehiculos.capacidad_kg, 1)) * 100, 1) ELSE 0 END as load_factor'),
            ]);
    }

    public static function maintenanceBaseQuery(): Builder
    {
        return DB::table('mantenimiento')
            ->leftJoin('vehiculos', function ($join): void {
                $join->on('vehiculos.id', '=', 'mantenimiento.vehiculo_id')
                    ->orOn('vehiculos.id', '=', 'mantenimiento.vehicle_id');
            })
            ->leftJoin('tipo_mantenimiento', 'tipo_mantenimiento.id', '=', 'mantenimiento.tipo_id')
            ->select([
                'mantenimiento.*',
                DB::raw('COALESCE(vehiculos.placa, vehiculos.plate) as vehicle_plate'),
                DB::raw('COALESCE(tipo_mantenimiento.nombre, mantenimiento.type) as maintenance_type_name'),
            ]);
    }

    public static function evidenceBaseQuery(): Builder
    {
        return DB::table('evidencias')
            ->leftJoin('paquetes', 'paquetes.id', '=', 'evidencias.package_id')
            ->leftJoin('asignaciones', 'asignaciones.id', '=', 'evidencias.asignacion_id')
            ->leftJoin('rutas', function ($join): void {
                $join->on('rutas.id', '=', 'evidencias.route_id')
                    ->orOn('rutas.id', '=', 'asignaciones.ruta_id')
                    ->orOn('rutas.id', '=', 'asignaciones.route_id');
            })
            ->leftJoin('conductores', function ($join): void {
                $join->on('conductores.id', '=', 'evidencias.driver_id')
                    ->orOn('conductores.id', '=', 'asignaciones.conductor_id')
                    ->orOn('conductores.id', '=', 'asignaciones.driver_id');
            })
            ->leftJoin('personas as driver_person', 'driver_person.id', '=', 'conductores.persona_id')
            ->select([
                'evidencias.*',
                DB::raw('COALESCE(paquetes.codigo_tracking, paquetes.tracking_code, paquetes.codigo_rastreo) as shipment_tracking'),
                DB::raw('COALESCE(rutas.codigo, rutas.route_code) as route_code'),
                DB::raw('COALESCE(conductores.name, CONCAT_WS(" ", driver_person.nombre, driver_person.apellido_paterno), CONCAT_WS(" ", driver_person.nombres, driver_person.apellidos)) as driver_name'),
            ]);
    }

    public static function customerPayload(object $customer): array
    {
        $code = self::pickString($customer, ['codigo_cliente']) ?: sprintf('CLI-%03d', $customer->id);
        $name = self::pickString($customer, ['name'])
            ?: trim(implode(' ', array_filter([$customer->nombre ?? null, $customer->apellido_paterno ?? null])));

        return [
            'id' => (int) $customer->id,
            'code' => $code,
            'name' => $name,
            'email' => $customer->email ?? null,
            'phone' => self::pickString($customer, ['phone', 'telefono']),
            'serviceLevel' => self::pickString($customer, ['nivel_servicio']) ?: 'estandar',
            'addresses' => [],
        ];
    }

    public static function driverPayload(object $driver): array
    {
        return [
            'id' => (int) $driver->id,
            'personId' => (int) ($driver->persona_id ?? 0),
            'name' => self::pickString($driver, ['display_name']) ?: 'Sin conductor',
            'phone' => self::pickString($driver, ['phone', 'telefono']),
            'status' => self::pickString($driver, ['estado_nombre', 'status']) ?: 'Disponible',
            'active' => (bool) ($driver->activo ?? 1),
            'deliveriesToday' => (int) ($driver->deliveries_today ?? 0),
            'shift' => self::pickString($driver, ['shift_window']) ?: 'Sin turno',
        ];
    }

    public static function vehiclePayload(object $vehicle): array
    {
        $plate = self::pickString($vehicle, ['placa', 'plate']);
        $type = self::pickString($vehicle, ['tipo_nombre', 'type']) ?: 'Sin tipo';
        $status = self::pickString($vehicle, ['estado_nombre', 'status', 'estado']) ?: 'Disponible';
        $capacityValue = self::pickString($vehicle, ['capacidad']);
        $capacityKg = (int) ($vehicle->capacity_kg ?? $vehicle->capacidad_kg ?? 0);

        return [
            'id' => (int) $vehicle->id,
            'plate' => $plate,
            'type' => $type,
            'status' => $status,
            'capacity' => $capacityValue ?: ($capacityKg ? $capacityKg.' kg' : '0 kg'),
            'capacityKg' => $capacityKg,
            'fuelConsumptionKm' => (float) ($vehicle->fuel_consumption_km ?? $vehicle->consumo_km ?? 0),
            'maintenance' => (bool) ($vehicle->maintenance_active ?? false),
        ];
    }

    public static function maintenancePayload(object $maintenance): array
    {
        return [
            'id' => (int) $maintenance->id,
            'vehicleId' => self::pickInt($maintenance, ['vehiculo_id', 'vehicle_id']),
            'vehiclePlate' => self::pickString($maintenance, ['vehicle_plate']) ?: 'Sin unidad',
            'type' => self::pickString($maintenance, ['maintenance_type_name', 'type']) ?: 'General',
            'scheduledDate' => self::pickString($maintenance, ['scheduled_date', 'fecha']),
            'completionDate' => self::pickString($maintenance, ['completion_date']),
            'cost' => (float) ($maintenance->cost ?? $maintenance->costo ?? 0),
            'description' => self::pickString($maintenance, ['description', 'descripcion']) ?: '',
            'kmAtMaintenance' => (int) ($maintenance->km_at_maintenance ?? 0),
            'status' => self::pickString($maintenance, ['status']) ?: 'scheduled',
            'createdAt' => $maintenance->created_at ?? null,
        ];
    }

    public static function evidencePayload(object $evidence, ?Request $request = null): array
    {
        $signaturePath = self::pickString($evidence, ['signature_path']);
        $photoPath = self::pickString($evidence, ['photo_path', 'url_imagen']);

        return [
            'id' => (int) $evidence->id,
            'shipmentId' => self::pickInt($evidence, ['package_id']),
            'tracking' => self::pickString($evidence, ['shipment_tracking']) ?: 'Sin tracking',
            'routeId' => self::pickInt($evidence, ['route_id']),
            'routeCode' => self::pickString($evidence, ['route_code']) ?: 'Sin ruta',
            'driverId' => self::pickInt($evidence, ['driver_id']),
            'driverName' => self::pickString($evidence, ['driver_name']) ?: 'Sin conductor',
            'deliveryTimestamp' => self::pickString($evidence, ['delivery_timestamp', 'fecha']) ?: ($evidence->created_at ?? null),
            'recipientName' => self::pickString($evidence, ['recipient_name']) ?: 'Sin receptor',
            'signatureText' => self::pickString($evidence, ['firma']),
            'signatureUrl' => self::publicUploadUrl($signaturePath, $request),
            'photoUrl' => self::publicUploadUrl($photoPath, $request),
            'gpsLatitude' => self::pickNumeric($evidence, ['gps_latitude']),
            'gpsLongitude' => self::pickNumeric($evidence, ['gps_longitude']),
            'notes' => self::pickString($evidence, ['notes']) ?: '',
            'status' => self::pickString($evidence, ['status']) ?: 'delivered',
            'createdAt' => $evidence->created_at ?? null,
        ];
    }

    public static function pickString(object $row, array $fields): ?string
    {
        $value = self::pickValue($row, $fields);

        return $value === null ? null : (string) $value;
    }

    public static function pickValue(object $row, array $fields): mixed
    {
        foreach ($fields as $field) {
            if (property_exists($row, $field) && $row->{$field} !== null && $row->{$field} !== '') {
                return $row->{$field};
            }
        }

        return null;
    }

    public static function pickInt(object $row, array $fields): ?int
    {
        foreach ($fields as $field) {
            if (property_exists($row, $field) && $row->{$field} !== null && $row->{$field} !== '') {
                return (int) $row->{$field};
            }
        }

        return null;
    }

    public static function pickNumeric(object $row, array $fields): int|float|null
    {
        foreach ($fields as $field) {
            if (property_exists($row, $field) && $row->{$field} !== null && $row->{$field} !== '') {
                return is_numeric($row->{$field}) ? $row->{$field} + 0 : null;
            }
        }

        return null;
    }

    public static function generateToken(): string
    {
        return Str::random(60);
    }

    public static function normalizePriority(?string $priority): ?string
    {
        if (! $priority) {
            return null;
        }

        $normalized = strtolower(trim(Str::ascii($priority)));

        return match ($normalized) {
            'estandar', 'standard', 'normal' => 'standard',
            'alta', 'high', 'priority' => 'high',
            'urgente', 'urgent', 'express', 'expres' => 'express',
            default => $normalized !== '' ? $normalized : null,
        };
    }

    public static function publicUploadUrl(?string $path, ?Request $request = null): ?string
    {
        if (! $path) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $normalized = '/'.ltrim(str_replace('\\', '/', $path), '/');

        if (! $request) {
            return $normalized;
        }

        return rtrim($request->root(), '/').$normalized;
    }

    public static function settingValue(string $key, mixed $default = null): mixed
    {
        $value = DB::table('configuracion_sistema')->where('clave', $key)->value('valor');

        return $value !== null ? $value : $default;
    }

    public static function settingBool(string $key, bool $default = false): bool
    {
        $value = self::settingValue($key, $default ? '1' : '0');

        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    public static function packageStatusIdFor(?string $status): ?int
    {
        if (! $status) {
            return null;
        }

        return DB::table('estado_paquete')->where('nombre', $status)->value('id');
    }

    public static function recordTrackingEvent(
        int $shipmentId,
        string $type,
        string $description,
        ?string $location = null,
        ?string $status = null,
        int|float|null $latitude = null,
        int|float|null $longitude = null,
        mixed $timestamp = null,
    ): void {
        DB::table('tracking')->insert([
            'paquete_id' => $shipmentId,
            'package_id' => $shipmentId,
            'estado_id' => self::packageStatusIdFor($status),
            'event_type' => $type,
            'description' => $description,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location' => $location,
            'timestamp_event' => $timestamp ?: now(),
            'fecha' => $timestamp ?: now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }
}
