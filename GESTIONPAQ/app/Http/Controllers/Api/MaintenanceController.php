<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorize($request)) {
            return $response;
        }

        $query = LogisticsSupport::maintenanceBaseQuery()->orderByDesc('mantenimiento.id');

        if ($request->filled('vehicleId')) {
            $query->where(function ($filter) use ($request): void {
                $filter->where('mantenimiento.vehiculo_id', $request->integer('vehicleId'))
                    ->orWhere('mantenimiento.vehicle_id', $request->integer('vehicleId'));
            });
        }

        return ApiResponder::success(
            $query->get()->map(fn ($item) => LogisticsSupport::maintenancePayload($item))->values()->all()
        );
    }

    public function show(Request $request, int $maintenance): JsonResponse
    {
        if ($response = $this->authorize($request)) {
            return $response;
        }

        $item = LogisticsSupport::maintenanceBaseQuery()->where('mantenimiento.id', $maintenance)->first();

        return $item
            ? ApiResponder::success(LogisticsSupport::maintenancePayload($item))
            : ApiResponder::error('Mantenimiento no encontrado.', 404);
    }

    public function options(Request $request): JsonResponse
    {
        if ($response = $this->authorize($request)) {
            return $response;
        }

        $types = DB::table('tipo_mantenimiento')->orderBy('nombre')->pluck('nombre')->all();

        if (! count($types)) {
            $types = ['Preventivo', 'Correctivo', 'Inspeccion'];
        }

        return ApiResponder::success([
            'vehicles' => DB::table('vehiculos')->orderByRaw('COALESCE(placa, plate)')->get()->map(fn ($item) => [
                'id' => (int) $item->id,
                'plate' => $item->placa ?: $item->plate,
            ])->values(),
            'types' => $types,
            'statuses' => ['scheduled', 'in_progress', 'completed', 'cancelled'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($response = $this->authorize($request)) {
            return $response;
        }

        $payload = $request->validate([
            'vehicleId' => ['required', 'integer'],
            'type' => ['required', 'string', 'max:80'],
            'scheduledDate' => ['required', 'date'],
            'completionDate' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:40'],
            'cost' => ['nullable', 'numeric'],
            'kmAtMaintenance' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
        ]);

        $typeId = DB::table('tipo_mantenimiento')->where('nombre', $payload['type'])->value('id');
        $id = DB::table('mantenimiento')->insertGetId([
            'vehiculo_id' => $payload['vehicleId'],
            'vehicle_id' => $payload['vehicleId'],
            'tipo_id' => $typeId,
            'type' => $payload['type'],
            'fecha' => $payload['scheduledDate'],
            'scheduled_date' => $payload['scheduledDate'],
            'costo' => $payload['cost'] ?? 0,
            'cost' => $payload['cost'] ?? 0,
            'descripcion' => $payload['description'] ?? null,
            'description' => $payload['description'] ?? null,
            'completion_date' => $payload['completionDate'] ?? null,
            'km_at_maintenance' => $payload['kmAtMaintenance'] ?? 0,
            'status' => $payload['status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->syncVehicleStatus($payload['vehicleId']);

        return ApiResponder::success([
            'item' => LogisticsSupport::maintenancePayload(LogisticsSupport::maintenanceBaseQuery()->where('mantenimiento.id', $id)->first()),
            'message' => 'Evento de mantenimiento registrado correctamente.',
        ], 201);
    }

    public function update(Request $request, int $maintenance): JsonResponse
    {
        if ($response = $this->authorize($request)) {
            return $response;
        }

        $payload = $request->validate([
            'vehicleId' => ['sometimes', 'integer'],
            'type' => ['sometimes', 'string', 'max:80'],
            'scheduledDate' => ['sometimes', 'date'],
            'completionDate' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:40'],
            'cost' => ['nullable', 'numeric'],
            'kmAtMaintenance' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
        ]);

        $record = DB::table('mantenimiento')->where('id', $maintenance)->first();

        if (! $record) {
            return ApiResponder::error('Mantenimiento no encontrado.', 404);
        }

        $update = ['updated_at' => now()];

        if (array_key_exists('vehicleId', $payload)) {
            $update['vehiculo_id'] = $payload['vehicleId'];
            $update['vehicle_id'] = $payload['vehicleId'];
        }

        if (array_key_exists('type', $payload)) {
            $update['tipo_id'] = DB::table('tipo_mantenimiento')->where('nombre', $payload['type'])->value('id');
            $update['type'] = $payload['type'];
        }

        if (array_key_exists('scheduledDate', $payload)) {
            $update['fecha'] = $payload['scheduledDate'];
            $update['scheduled_date'] = $payload['scheduledDate'];
        }

        if (array_key_exists('completionDate', $payload)) {
            $update['completion_date'] = $payload['completionDate'];
        }

        if (array_key_exists('status', $payload)) {
            $update['status'] = $payload['status'];
        }

        if (array_key_exists('cost', $payload)) {
            $update['costo'] = $payload['cost'] ?? 0;
            $update['cost'] = $payload['cost'] ?? 0;
        }

        if (array_key_exists('kmAtMaintenance', $payload)) {
            $update['km_at_maintenance'] = $payload['kmAtMaintenance'] ?? 0;
        }

        if (array_key_exists('description', $payload)) {
            $update['descripcion'] = $payload['description'] ?? null;
            $update['description'] = $payload['description'] ?? null;
        }

        DB::table('mantenimiento')->where('id', $maintenance)->update($update);

        $vehicleId = (int) ($payload['vehicleId'] ?? $record->vehiculo_id ?? $record->vehicle_id ?? 0);
        if ($vehicleId) {
            $this->syncVehicleStatus($vehicleId);
        }

        return ApiResponder::success([
            'item' => LogisticsSupport::maintenancePayload(LogisticsSupport::maintenanceBaseQuery()->where('mantenimiento.id', $maintenance)->first()),
            'message' => 'Mantenimiento actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, int $maintenance): JsonResponse
    {
        if ($response = $this->authorize($request)) {
            return $response;
        }

        $record = DB::table('mantenimiento')->where('id', $maintenance)->first();

        if (! $record) {
            return response()->json(null, 204);
        }

        DB::table('mantenimiento')->where('id', $maintenance)->delete();

        $vehicleId = (int) ($record->vehiculo_id ?? $record->vehicle_id ?? 0);
        if ($vehicleId) {
            $this->syncVehicleStatus($vehicleId);
        }

        return response()->json(null, 204);
    }

    private function authorize(Request $request): ?JsonResponse
    {
        if (! in_array(LogisticsSupport::roleName(LogisticsSupport::apiUser($request)), ['admin', 'supervisor', 'dispatcher'], true)) {
            return ApiResponder::error('No tienes permisos para administrar mantenimiento.', 403);
        }

        return null;
    }

    private function syncVehicleStatus(int $vehicleId): void
    {
        $activeMaintenance = DB::table('mantenimiento')
            ->where(function ($query) use ($vehicleId): void {
                $query->where('vehiculo_id', $vehicleId)
                    ->orWhere('vehicle_id', $vehicleId);
            })
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->exists();

        $vehicle = DB::table('vehiculos')->where('id', $vehicleId)->first();

        if (! $vehicle) {
            return;
        }

        if ($activeMaintenance) {
            $status = 'Mantenimiento';
        } else {
            $current = strtolower((string) ($vehicle->status ?? $vehicle->estado ?? ''));
            $status = str_contains($current, 'manten') ? 'Operativo' : ($vehicle->status ?: $vehicle->estado ?: 'Operativo');
        }

        DB::table('vehiculos')->where('id', $vehicleId)->update([
            'estado' => $status,
            'status' => $status,
            'estado_id' => DB::table('estado_vehiculo')->where('nombre', $status)->value('id'),
            'updated_at' => now(),
        ]);
    }
}