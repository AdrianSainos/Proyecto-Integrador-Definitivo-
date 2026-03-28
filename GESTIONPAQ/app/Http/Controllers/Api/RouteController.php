<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsSupport;
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
            'status' => $payload['status'],
            'estado' => $payload['status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
        }

        DB::table('rutas')->where('id', $route)->update($update);

        return ApiResponder::success([
            'item' => LogisticsSupport::routePayload(LogisticsSupport::routeBaseQuery()->where('rutas.id', $route)->first()),
            'message' => 'Ruta actualizada correctamente.',
        ]);
    }

    public function destroy(int $route): JsonResponse
    {
        DB::table('asignaciones')->where('ruta_id', $route)->orWhere('route_id', $route)->delete();
        DB::table('rutas')->where('id', $route)->delete();

        return response()->json(null, 204);
    }
}