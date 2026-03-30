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

class RouteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return ApiResponder::success(
            LogisticsSupport::routeBaseQueryFor($request)->orderByDesc('rutas.id')->get()->map(fn ($item) => LogisticsSupport::routePayload($item))->values()->all()
        );
    }

    public function show(Request $request, int $route): JsonResponse
    {
        $item = LogisticsSupport::routeBaseQueryFor($request)->where('rutas.id', $route)->first();

        return $item
            ? ApiResponder::success(LogisticsSupport::routePayload($item))
            : ApiResponder::error('Ruta no encontrada.', 404);
    }

    public function options(): JsonResponse
    {
        return ApiResponder::success([
            'warehouses' => DB::table('almacenes')->orderBy('nombre')->get()->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $item->nombre,
                'city' => $item->city,
            ])->values(),
            'statuses' => DB::table('estado_ruta')->orderBy('nombre')->pluck('nombre')->all(),
            'vehicles' => DB::table('vehiculos')->orderBy('placa')->get()->map(fn ($item) => ['id' => (int) $item->id, 'plate' => $item->placa ?: $item->plate])->values(),
            'drivers' => DB::table('conductores')->leftJoin('personas', 'personas.id', '=', 'conductores.persona_id')->orderBy('conductores.id')->get()->map(fn ($item) => ['id' => (int) $item->id, 'name' => $item->name ?: trim(($item->nombre ?? '').' '.($item->apellido_paterno ?? ''))])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'warehouseId' => ['required', 'integer'],
            'distanceKm' => ['required', 'numeric'],
            'timeMinutes' => ['required', 'integer'],
            'status' => ['required', 'string'],
            'vehicleId' => ['nullable', 'integer'],
            'driverId' => ['nullable', 'integer'],
        ]);
        $payload['driverId'] = $this->normalizeDriverReference($payload['driverId'] ?? null);
        $statusId = DB::table('estado_ruta')->where('nombre', $payload['status'])->value('id');

        $id = DB::table('rutas')->insertGetId([
            'codigo' => sprintf('RUTA-%04d', (int) (DB::table('rutas')->max('id') + 1)),
            'almacen_origen_id' => $payload['warehouseId'],
            'origen_almacen_id' => $payload['warehouseId'],
            'warehouse_id' => $payload['warehouseId'],
            'distancia_km' => $payload['distanceKm'],
            'estimated_distance_km' => $payload['distanceKm'],
            'tiempo_estimado_min' => $payload['timeMinutes'],
            'estimated_time_minutes' => $payload['timeMinutes'],
            'vehicle_id' => $payload['vehicleId'] ?: null,
            'driver_id' => $payload['driverId'] ?: null,
            'estado_id' => $statusId,
            'status' => $payload['status'],
            'estado' => $payload['status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->syncRouteOperationalData($id);
        LogisticsPlanner::syncRouteMetrics($id);

        return ApiResponder::success([
            'item' => LogisticsSupport::routePayload(LogisticsSupport::routeBaseQuery()->where('rutas.id', $id)->first()),
            'message' => 'Ruta guardada correctamente.',
        ], 201);
    }

    public function update(Request $request, int $route): JsonResponse
    {
        $payload = $request->validate([
            'warehouseId' => ['sometimes', 'integer'],
            'distanceKm' => ['sometimes', 'numeric'],
            'timeMinutes' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'string'],
            'vehicleId' => ['nullable', 'integer'],
            'driverId' => ['nullable', 'integer'],
        ]);
        if (array_key_exists('driverId', $payload)) {
            $payload['driverId'] = $this->normalizeDriverReference($payload['driverId']);
        }
        $current = DB::table('rutas')
            ->where('id', $route)
            ->select(['id', 'driver_id', 'vehicle_id', 'status', 'estado'])
            ->first();

        $update = ['updated_at' => now()];

        if (array_key_exists('warehouseId', $payload)) {
            $update['almacen_origen_id'] = $payload['warehouseId'];
            $update['origen_almacen_id'] = $payload['warehouseId'];
            $update['warehouse_id'] = $payload['warehouseId'];
        }

        if (array_key_exists('distanceKm', $payload)) {
            $update['distancia_km'] = $payload['distanceKm'];
            $update['estimated_distance_km'] = $payload['distanceKm'];
        }

        if (array_key_exists('timeMinutes', $payload)) {
            $update['tiempo_estimado_min'] = $payload['timeMinutes'];
            $update['estimated_time_minutes'] = $payload['timeMinutes'];
        }

        if (array_key_exists('vehicleId', $payload)) {
            $update['vehicle_id'] = $payload['vehicleId'] ?: null;
        }

        if (array_key_exists('driverId', $payload)) {
            $update['driver_id'] = $payload['driverId'] ?: null;
        }

        if (array_key_exists('status', $payload)) {
            $update['status'] = $payload['status'];
            $update['estado'] = $payload['status'];
            $update['estado_id'] = DB::table('estado_ruta')->where('nombre', $payload['status'])->value('id');
        }

        DB::table('rutas')->where('id', $route)->update($update);
        $this->syncRouteOperationalData($route, $current);
        LogisticsPlanner::syncRouteMetrics($route);

        return ApiResponder::success([
            'item' => LogisticsSupport::routePayload(LogisticsSupport::routeBaseQuery()->where('rutas.id', $route)->first()),
            'message' => 'Ruta actualizada correctamente.',
        ]);
    }

    public function destroy(int $route): JsonResponse
    {
        $current = DB::table('rutas')
            ->where('id', $route)
            ->select(['id', 'driver_id'])
            ->first();

        if (! $current) {
            return response()->json(null, 204);
        }

        DB::transaction(function () use ($route): void {
            $assignments = $this->routeAssignmentsQuery($route)->get(['id', 'package_id']);
            $assignmentIds = $assignments
                ->pluck('id')
                ->filter(fn ($id) => (int) $id > 0)
                ->values()
                ->all();
            $packageIds = $assignments
                ->pluck('package_id')
                ->filter(fn ($id) => (int) $id > 0)
                ->unique()
                ->values()
                ->all();
            $packages = ! empty($packageIds)
                ? DB::table('paquetes')->whereIn('id', $packageIds)->get(['id', 'scheduled_date', 'status', 'estado'])
                : collect();

            if (! empty($assignmentIds)) {
                DB::table('evidencias')
                    ->whereIn('asignacion_id', $assignmentIds)
                    ->update([
                        'asignacion_id' => null,
                        'route_id' => null,
                        'updated_at' => now(),
                    ]);
            }

            DB::table('evidencias')
                ->where('route_id', $route)
                ->update([
                    'route_id' => null,
                    'updated_at' => now(),
                ]);

            DB::table('ruta_paradas')
                ->where(function ($query) use ($route): void {
                    $query->where('ruta_id', $route)
                        ->orWhere('route_id', $route);
                })
                ->delete();

            $this->routeAssignmentsQuery($route)->delete();

            foreach ($packages as $package) {
                if ($this->shipmentWasDelivered($package)) {
                    continue;
                }

                $status = $this->shipmentStatusAfterRouteRemoval($package->scheduled_date ?? null);

                DB::table('paquetes')->where('id', $package->id)->update([
                    'estado_id' => LogisticsSupport::packageStatusIdFor($status),
                    'estado' => $status,
                    'status' => $status,
                    'assigned_at' => null,
                    'promised_date' => $package->scheduled_date ?? null,
                    'eta_at' => null,
                    'updated_at' => now(),
                ]);

                LogisticsSupport::recordTrackingEvent(
                    (int) $package->id,
                    'Replanificacion',
                    'La ruta asignada fue eliminada. El envio vuelve a estado '.$status.'.',
                    'Mesa de despacho',
                    $status,
                );
            }

            DB::table('rutas')->where('id', $route)->delete();
        });

        if ($current && (int) ($current->driver_id ?? 0) > 0) {
            $this->refreshDriverOperationalState((int) $current->driver_id);
        }

        return response()->json(null, 204);
    }

    private function syncRouteOperationalData(int $routeId, ?object $previousRoute = null): void
    {
        $route = DB::table('rutas')
            ->where('id', $routeId)
            ->select(['id', 'driver_id', 'vehicle_id', 'status', 'estado'])
            ->first();

        if (! $route) {
            return;
        }

        DB::table('asignaciones')
            ->where(function ($query) use ($routeId): void {
                $query->where('ruta_id', $routeId)
                    ->orWhere('route_id', $routeId);
            })
            ->update([
                'conductor_id' => $route->driver_id ?: null,
                'driver_id' => $route->driver_id ?: null,
                'vehiculo_id' => $route->vehicle_id ?: null,
                'vehicle_id' => $route->vehicle_id ?: null,
                'updated_at' => now(),
            ]);

        if ((int) ($route->driver_id ?? 0) > 0) {
            $this->syncDriverOperationalState(
                (int) $route->driver_id,
                $route->vehicle_id ? (int) $route->vehicle_id : null,
                $route->status ?? $route->estado
            );
        }

        $previousDriverId = (int) ($previousRoute->driver_id ?? 0);
        $currentDriverId = (int) ($route->driver_id ?? 0);

        if ($previousDriverId > 0 && $previousDriverId !== $currentDriverId) {
            $this->refreshDriverOperationalState($previousDriverId);
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

    private function refreshDriverOperationalState(int $driverId): void
    {
        $activeRoute = DB::table('rutas')
            ->where('driver_id', $driverId)
            ->whereRaw('LOWER(COALESCE(status, estado, "")) not in (?, ?)', ['completada', 'cancelada'])
            ->orderByRaw('CASE WHEN LOWER(COALESCE(status, estado, "")) like ? THEN 0 ELSE 1 END', ['%ejec%'])
            ->orderByDesc('scheduled_date')
            ->orderByDesc('id')
            ->select(['vehicle_id', 'status', 'estado'])
            ->first();

        if ($activeRoute) {
            $this->syncDriverOperationalState(
                $driverId,
                $activeRoute->vehicle_id ? (int) $activeRoute->vehicle_id : null,
                $activeRoute->status ?? $activeRoute->estado
            );

            return;
        }

        $statusId = DB::table('estado_conductor')->where('nombre', 'Disponible')->value('id');
        $updates = [
            'status' => 'Disponible',
            'updated_at' => now(),
        ];

        if ($statusId) {
            $updates['estado_id'] = $statusId;
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

    private function normalizeDriverReference(mixed $driverReference): ?int
    {
        $driverId = (int) $driverReference;

        if ($driverId <= 0) {
            return null;
        }

        $direct = DB::table('conductores')->where('id', $driverId)->value('id');

        if ($direct) {
            return (int) $direct;
        }

        $fromPersona = DB::table('conductores')->where('persona_id', $driverId)->value('id');

        return $fromPersona ? (int) $fromPersona : null;
    }

    private function routeAssignmentsQuery(int $route)
    {
        return DB::table('asignaciones')->where(function ($query) use ($route): void {
            $query->where('ruta_id', $route)
                ->orWhere('route_id', $route);
        });
    }

    private function shipmentWasDelivered(object $package): bool
    {
        $status = strtolower((string) ($package->status ?? $package->estado ?? ''));

        return str_contains($status, 'entreg');
    }

    private function shipmentStatusAfterRouteRemoval(?string $scheduledDate): string
    {
        if (! $scheduledDate) {
            return 'Pendiente';
        }

        try {
            return Carbon::parse($scheduledDate)->isFuture() ? 'Planificado' : 'Pendiente';
        } catch (\Throwable) {
            return 'Pendiente';
        }
    }
}
