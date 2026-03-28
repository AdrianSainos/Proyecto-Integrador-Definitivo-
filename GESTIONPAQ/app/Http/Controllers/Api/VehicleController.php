<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponder::success($this->vehicles()->get()->map(fn ($item) => LogisticsSupport::vehiclePayload($item))->values()->all());
    }

    public function show(int $vehicle): JsonResponse
    {
        $item = $this->vehicles()->where('vehiculos.id', $vehicle)->first();

        return $item
            ? ApiResponder::success(LogisticsSupport::vehiclePayload($item))
            : ApiResponder::error('Vehiculo no encontrado.', 404);
    }

    public function options(): JsonResponse
    {
        return ApiResponder::success([
            'types' => DB::table('tipo_vehiculo')->orderBy('nombre')->pluck('nombre')->all(),
            'statuses' => DB::table('estado_vehiculo')->orderBy('nombre')->pluck('nombre')->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'plate' => ['required', 'string', 'max:20'],
            'type' => ['required', 'string', 'max:60'],
            'status' => ['required', 'string', 'max:40'],
            'capacity' => ['required', 'string', 'max:80'],
            'fuelConsumptionKm' => ['nullable', 'numeric'],
        ]);

        $typeId = DB::table('tipo_vehiculo')->where('nombre', $payload['type'])->value('id');
        $statusId = DB::table('estado_vehiculo')->where('nombre', $payload['status'])->value('id');
        $id = DB::table('vehiculos')->insertGetId([
            'placa' => $payload['plate'],
            'plate' => $payload['plate'],
            'type' => $payload['type'],
            'tipo_id' => $typeId,
            'estado' => $payload['status'],
            'status' => $payload['status'],
            'estado_id' => $statusId,
            'capacidad' => preg_replace('/[^\d.]/', '', $payload['capacity']) ?: null,
            'capacity_kg' => (int) preg_replace('/[^\d]/', '', $payload['capacity']),
            'capacidad_kg' => (int) preg_replace('/[^\d]/', '', $payload['capacity']),
            'fuel_consumption_km' => $payload['fuelConsumptionKm'] ?? 0,
            'consumo_km' => $payload['fuelConsumptionKm'] ?? 0,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ApiResponder::success([
            'item' => LogisticsSupport::vehiclePayload($this->vehicles()->where('vehiculos.id', $id)->first()),
            'message' => 'Vehiculo guardado correctamente.',
        ], 201);
    }

    public function update(Request $request, int $vehicle): JsonResponse
    {
        $payload = $request->validate([
            'plate' => ['sometimes', 'string', 'max:20'],
            'type' => ['sometimes', 'string', 'max:60'],
            'status' => ['sometimes', 'string', 'max:40'],
            'capacity' => ['sometimes', 'string', 'max:80'],
            'fuelConsumptionKm' => ['nullable', 'numeric'],
        ]);

        $update = ['updated_at' => now()];
        if (isset($payload['plate'])) {
            $update['placa'] = $payload['plate'];
            $update['plate'] = $payload['plate'];
        }
        if (isset($payload['type'])) {
            $update['type'] = $payload['type'];
            $update['tipo_id'] = DB::table('tipo_vehiculo')->where('nombre', $payload['type'])->value('id');
        }
        if (isset($payload['status'])) {
            $update['estado'] = $payload['status'];
            $update['status'] = $payload['status'];
            $update['estado_id'] = DB::table('estado_vehiculo')->where('nombre', $payload['status'])->value('id');
        }
        if (isset($payload['capacity'])) {
            $update['capacidad'] = preg_replace('/[^\d.]/', '', $payload['capacity']) ?: null;
            $update['capacity_kg'] = (int) preg_replace('/[^\d]/', '', $payload['capacity']);
            $update['capacidad_kg'] = (int) preg_replace('/[^\d]/', '', $payload['capacity']);
        }
        if (array_key_exists('fuelConsumptionKm', $payload)) {
            $update['fuel_consumption_km'] = $payload['fuelConsumptionKm'] ?? 0;
            $update['consumo_km'] = $payload['fuelConsumptionKm'] ?? 0;
        }

        DB::table('vehiculos')->where('id', $vehicle)->update($update);

        return ApiResponder::success([
            'item' => LogisticsSupport::vehiclePayload($this->vehicles()->where('vehiculos.id', $vehicle)->first()),
            'message' => 'Vehiculo actualizado correctamente.',
        ]);
    }

    public function destroy(int $vehicle): JsonResponse
    {
        DB::table('vehiculos')->where('id', $vehicle)->delete();

        return response()->json(null, 204);
    }

    private function vehicles()
    {
        return DB::table('vehiculos')
            ->leftJoin('tipo_vehiculo', 'tipo_vehiculo.id', '=', 'vehiculos.tipo_id')
            ->leftJoin('estado_vehiculo', 'estado_vehiculo.id', '=', 'vehiculos.estado_id')
            ->select([
                'vehiculos.*',
                'tipo_vehiculo.nombre as tipo_nombre',
                'estado_vehiculo.nombre as estado_nombre',
                DB::raw('exists(select 1 from mantenimiento where mantenimiento.vehiculo_id = vehiculos.id and (mantenimiento.status = "scheduled" or mantenimiento.status = "in_progress" or mantenimiento.completion_date is null)) as maintenance_active'),
            ]);
    }
}