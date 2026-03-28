<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = LogisticsSupport::apiUser($request);
        $role = LogisticsSupport::roleName($user);
        $shipments = LogisticsSupport::shipmentBaseQueryFor($request)->get()->map(fn ($item) => LogisticsSupport::shipmentPayload($item));

        $routes = LogisticsSupport::routeBaseQueryFor($request)->get()->map(fn ($item) => LogisticsSupport::routePayload($item));

        if ($role === 'customer') {
            $visibleRouteIds = $shipments->pluck('routeId')->filter()->unique()->values();
            $routes = $routes->whereIn('id', $visibleRouteIds)->values();
        }

        $vehicles = DB::table('vehiculos')
            ->leftJoin('tipo_vehiculo', 'tipo_vehiculo.id', '=', 'vehiculos.tipo_id')
            ->leftJoin('estado_vehiculo', 'estado_vehiculo.id', '=', 'vehiculos.estado_id')
            ->select([
                'vehiculos.*',
                'tipo_vehiculo.nombre as tipo_nombre',
                'estado_vehiculo.nombre as estado_nombre',
                DB::raw('exists(select 1 from mantenimiento where mantenimiento.vehiculo_id = vehiculos.id and (mantenimiento.status = "scheduled" or mantenimiento.status = "in_progress" or mantenimiento.completion_date is null)) as maintenance_active'),
            ])
            ->get()
            ->map(fn ($item) => LogisticsSupport::vehiclePayload($item));
        $drivers = DB::table('conductores')
            ->leftJoin('personas', 'personas.id', '=', 'conductores.persona_id')
            ->leftJoin('estado_conductor', 'estado_conductor.id', '=', 'conductores.estado_id')
            ->leftJoinSub(
                DB::table('turnos_conductor')
                    ->selectRaw('driver_id, MAX(shift_date) as latest_shift_date')
                    ->groupBy('driver_id'),
                'latest_shift',
                fn ($join) => $join->on('latest_shift.driver_id', '=', 'conductores.id')
            )
            ->leftJoin('turnos_conductor', function ($join): void {
                $join->on('turnos_conductor.driver_id', '=', 'conductores.id')
                    ->on('turnos_conductor.shift_date', '=', 'latest_shift.latest_shift_date');
            })
            ->select([
                'conductores.*',
                DB::raw('COALESCE(conductores.name, CONCAT_WS(" ", personas.nombre, personas.apellido_paterno), CONCAT_WS(" ", personas.nombres, personas.apellidos)) as display_name'),
                'estado_conductor.nombre as estado_nombre',
                DB::raw('COALESCE(turnos_conductor.successful_deliveries, 0) as deliveries_today'),
                DB::raw('CONCAT_WS(" - ", turnos_conductor.start_time, turnos_conductor.end_time) as shift_window'),
            ])
            ->get()
            ->map(fn ($item) => LogisticsSupport::driverPayload($item));
        $customers = DB::table('clientes')
            ->leftJoin('personas', 'personas.id', '=', 'clientes.persona_id')
            ->select([
                'clientes.*',
                'personas.nombre',
                'personas.apellido_paterno',
            ])
            ->get()
            ->map(fn ($item) => LogisticsSupport::customerPayload($item));

        if (in_array($role, ['customer', 'driver'], true)) {
            $visibleVehicleIds = $shipments->pluck('vehicleId')->filter()->unique()->values();
            $visibleDriverIds = $shipments->pluck('driverId')->filter()->unique()->values();
            $visibleCustomerIds = $shipments->pluck('senderId')->filter()->unique()->values();

            $vehicles = $vehicles->whereIn('id', $visibleVehicleIds)->values();
            $drivers = $drivers->whereIn('id', $visibleDriverIds)->values();
            $customers = $customers->whereIn('id', $visibleCustomerIds)->values();
        }

        $pending = $shipments->where('status', 'Pendiente')->count();
        $inRoute = $shipments->where('status', 'En ruta')->count();
        $delivered = $shipments->filter(fn ($item) => str_contains(strtolower($item['status']), 'entreg'))->count();

        return ApiResponder::success([
            'kpis' => [
                ['title' => 'Paquetes Totales', 'value' => $shipments->count(), 'detail' => 'Volumen operativo total'],
                ['title' => 'Rutas', 'value' => $routes->count(), 'detail' => 'Planeadas y en ejecucion'],
                ['title' => 'Vehiculos', 'value' => $vehicles->count(), 'detail' => 'Disponibilidad de flota'],
                ['title' => 'Conductores', 'value' => $drivers->count(), 'detail' => 'Capacidad de despacho'],
            ],
            'richKpis' => [
                ['title' => 'Envios totales', 'value' => $shipments->count(), 'tone' => 'primary'],
                ['title' => 'Pendientes', 'value' => $pending, 'tone' => 'warning'],
                ['title' => 'En ruta', 'value' => $inRoute, 'tone' => 'info'],
                ['title' => 'Entregados hoy', 'value' => $delivered, 'tone' => 'success'],
            ],
            'strip' => [
                ['title' => 'Despachos activos', 'value' => $routes->where('status', 'En ejecucion')->count(), 'subtitle' => 'Ventanas sincronizadas', 'className' => 'accent-soft'],
                ['title' => 'Nivel SLA', 'value' => $shipments->count() ? '96.0%' : '0%', 'subtitle' => 'Lectura operativa', 'className' => 'brand-soft'],
                ['title' => 'Despacho automatizado', 'value' => 'Reglas REST', 'subtitle' => 'API lista para frontend', 'className' => 'dark-gradient'],
                ['title' => 'Capacidad usada', 'value' => $vehicles->count() ? '68%' : '0%', 'subtitle' => 'Flota + rutas', 'className' => ''],
            ],
            'charts' => [
                'operationalEvolution' => [8, 12, 14, 16, 18, 17, 20],
                'packageStatus' => [$pending, $inRoute, $delivered, max($shipments->count() - ($pending + $inRoute + $delivered), 0)],
                'deliveriesByHour' => [1, 2, 3, 5, 7, 4, 2],
                'routeState' => [
                    $routes->where('status', 'Preparacion')->count(),
                    $routes->where('status', 'En ejecucion')->count(),
                    $routes->where('status', 'Completada')->count(),
                    $routes->where('status', 'Cancelada')->count(),
                ],
            ],
            'exceptions' => [
                'pendingDeparture' => $shipments->where('status', 'Pendiente')->take(3)->values(),
                'maintenanceUnits' => $vehicles->where('maintenance', true)->take(3)->values(),
                'outOfShiftDrivers' => $drivers->filter(fn ($item) => str_contains(strtolower($item['status']), 'fuera'))->take(3)->values(),
                'activeRoutes' => $routes->filter(fn ($item) => in_array($item['status'], ['En ejecucion', 'Preparacion'], true))->take(4)->values(),
            ],
            'pulse' => [
                'completedRoutes' => $routes->where('status', 'Completada')->count(),
                'vehiclesInUse' => $vehicles->filter(fn ($item) => in_array($item['status'], ['Operativo', 'Disponible'], true))->count(),
                'activeDrivers' => $drivers->filter(fn ($item) => ! str_contains(strtolower($item['status']), 'fuera'))->count(),
            ],
            'leaderboards' => [
                'drivers' => $drivers->sortByDesc('deliveriesToday')->take(3)->values(),
                'customers' => $customers->take(3)->values(),
            ],
        ]);
    }
}