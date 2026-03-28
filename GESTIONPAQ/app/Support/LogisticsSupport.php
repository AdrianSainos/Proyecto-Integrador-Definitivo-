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

            return $query->where('conductores.persona_id', $personaId);
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

        return [
            'id' => (int) $route->id,
            'code' => $route->codigo ?: $route->route_code ?: sprintf('RUTA-%04d', $route->id),
            'warehouseId' => self::pickInt($route, ['almacen_origen_id', 'origen_almacen_id', 'warehouse_id']),
            'warehouseName' => $route->warehouse_name ?: 'Sin almacen',
            'distanceKm' => (float) ($route->distancia_km ?? $route->estimated_distance_km ?? 0),
            'timeMinutes' => (int) ($route->tiempo_estimado_min ?? $route->estimated_time_minutes ?? 0),
            'status' => $route->estado_ruta_nombre ?: $route->status ?: $route->estado ?: 'Planeada',
            'vehicleId' => self::pickInt($route, ['vehicle_id']),
            'vehiclePlate' => $route->vehicle_plate,
            'driverId' => self::pickInt($route, ['driver_id']),
            'driverName' => $route->driver_name,
            'scheduledDate' => $route->scheduled_date,
            'optimizationScore' => (float) ($route->optimization_score ?? 0),
        ];
    }

    public static function shipmentPayload(?object $shipment): ?array
    {
        if (! $shipment) {
            return null;
        }

        $routeCode = $shipment->route_code ?: $shipment->route_alias ?: 'Pendiente';
        $status = $shipment->estado_paquete_nombre ?: $shipment->status ?: $shipment->estado ?: 'Pendiente';

        return [
            'id' => (int) $shipment->id,
            'tracking' => $shipment->codigo_tracking ?: $shipment->tracking_code ?: $shipment->codigo_rastreo,
            'senderId' => self::pickInt($shipment, ['sender_id', 'cliente_id']),
            'recipientId' => self::pickInt($shipment, ['recipient_id']),
            'senderName' => $shipment->sender_name ?: $shipment->cliente_nombre ?: 'Sin remitente',
            'recipientName' => $shipment->recipient_name ?: 'Sin destinatario',
            'customerName' => $shipment->sender_name ?: $shipment->cliente_nombre ?: 'Sin cliente',
            'originWarehouseId' => self::pickInt($shipment, ['origin_warehouse_id']),
            'warehouseName' => $shipment->warehouse_name ?: 'Asignacion automatica',
            'routeId' => self::pickInt($shipment, ['route_id']),
            'routeCode' => $routeCode,
            'routeStatus' => $shipment->route_status ?: 'Sin ruta',
            'vehicleId' => self::pickInt($shipment, ['vehicle_id']),
            'vehiclePlate' => $shipment->vehicle_plate ?: 'Pendiente',
            'driverId' => self::pickInt($shipment, ['driver_id']),
            'driverName' => $shipment->driver_name ?: 'Pendiente',
            'packageType' => $shipment->tipo_paquete_nombre ?: $shipment->package_type ?: 'Carga general',
            'initialStatus' => $status,
            'status' => $status,
            'priority' => $shipment->priority ?: 'standard',
            'weightKg' => (float) self::pickNumeric($shipment, ['weight_kg', 'peso_kg', 'peso']),
            'volumeM3' => (float) self::pickNumeric($shipment, ['volume_m3', 'volumen_m3', 'volumen']),
            'quantity' => (int) ($shipment->quantity ?? 1),
            'scheduledDate' => $shipment->scheduled_date ?: ($shipment->fecha_estimada_entrega ? substr((string) $shipment->fecha_estimada_entrega, 0, 10) : null),
            'originAddress' => $shipment->origin_address ?: $shipment->origen_referencia ?: '',
            'destinationAddressId' => self::pickInt($shipment, ['recipient_address_id', 'direccion_destino_id', 'destino_direccion_id']),
            'destinationAddress' => $shipment->recipient_address ?: $shipment->destino_referencia ?: '',
            'destinationCity' => $shipment->recipient_city ?: $shipment->destino_ciudad ?: '',
            'destinationState' => $shipment->recipient_state ?: $shipment->destino_estado ?: '',
            'destinationPostalCode' => $shipment->recipient_postal_code ?: $shipment->destino_codigo_postal ?: '',
            'destinationLatitude' => self::pickNumeric($shipment, ['recipient_latitude']),
            'destinationLongitude' => self::pickNumeric($shipment, ['recipient_longitude']),
            'description' => $shipment->description ?: $shipment->descripcion ?: '',
            'declaredValue' => (float) ($shipment->declared_value ?? 0),
            'createdAt' => $shipment->created_at,
            'latestAssignment' => [
                'route' => $routeCode,
                'vehicle' => $shipment->vehicle_plate,
                'driver' => $shipment->driver_name,
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
        return DB::table('rutas')
            ->leftJoin('almacenes', 'almacenes.id', '=', 'rutas.almacen_origen_id')
            ->leftJoin('estado_ruta', 'estado_ruta.id', '=', 'rutas.estado_id')
            ->leftJoin('vehiculos', 'vehiculos.id', '=', 'rutas.vehicle_id')
            ->leftJoin('conductores', 'conductores.id', '=', 'rutas.driver_id')
            ->leftJoin('personas as driver_person', 'driver_person.id', '=', 'conductores.persona_id')
            ->select([
                'rutas.*',
                DB::raw('COALESCE(almacenes.nombre, almacenes.code, almacenes.codigo) as warehouse_name'),
                DB::raw('COALESCE(estado_ruta.nombre, rutas.status, rutas.estado) as estado_ruta_nombre'),
                DB::raw('COALESCE(vehiculos.placa, vehiculos.plate) as vehicle_plate'),
                DB::raw('COALESCE(conductores.name, CONCAT_WS(" ", driver_person.nombre, driver_person.apellido_paterno), CONCAT_WS(" ", driver_person.nombres, driver_person.apellidos)) as driver_name'),
            ]);
    }

    public static function customerPayload(object $customer): array
    {
        return [
            'id' => (int) $customer->id,
            'code' => $customer->codigo_cliente ?: sprintf('CLI-%03d', $customer->id),
            'name' => $customer->name ?: trim(implode(' ', array_filter([$customer->nombre, $customer->apellido_paterno]))),
            'email' => $customer->email,
            'phone' => $customer->phone ?: $customer->telefono,
            'serviceLevel' => $customer->nivel_servicio ?: 'estandar',
            'addresses' => [],
        ];
    }

    public static function driverPayload(object $driver): array
    {
        return [
            'id' => (int) $driver->id,
            'personId' => (int) ($driver->persona_id ?? 0),
            'name' => $driver->display_name,
            'phone' => $driver->phone ?: $driver->telefono,
            'status' => $driver->estado_nombre ?: $driver->status ?: 'Disponible',
            'active' => (bool) ($driver->activo ?? 1),
            'deliveriesToday' => (int) ($driver->deliveries_today ?? 0),
            'shift' => $driver->shift_window ?: 'Sin turno',
        ];
    }

    public static function vehiclePayload(object $vehicle): array
    {
        return [
            'id' => (int) $vehicle->id,
            'plate' => $vehicle->placa ?: $vehicle->plate,
            'type' => $vehicle->tipo_nombre ?: $vehicle->type ?: 'Sin tipo',
            'status' => $vehicle->estado_nombre ?: $vehicle->status ?: $vehicle->estado ?: 'Disponible',
            'capacity' => $vehicle->capacidad ?: ($vehicle->capacity_kg ? $vehicle->capacity_kg.' kg' : ($vehicle->capacidad_kg ? $vehicle->capacidad_kg.' kg' : '0 kg')),
            'fuelConsumptionKm' => (float) ($vehicle->fuel_consumption_km ?? $vehicle->consumo_km ?? 0),
            'maintenance' => (bool) ($vehicle->maintenance_active ?? false),
        ];
    }

    public static function pickInt(object $row, array $fields): ?int
    {
        foreach ($fields as $field) {
            if (isset($row->{$field}) && $row->{$field} !== null && $row->{$field} !== '') {
                return (int) $row->{$field};
            }
        }

        return null;
    }

    public static function pickNumeric(object $row, array $fields): int|float|null
    {
        foreach ($fields as $field) {
            if (isset($row->{$field}) && $row->{$field} !== null && $row->{$field} !== '') {
                return is_numeric($row->{$field}) ? $row->{$field} + 0 : null;
            }
        }

        return null;
    }

    public static function generateToken(): string
    {
        return Str::random(60);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }
}