<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponder::success($this->drivers()->get()->map(fn ($item) => LogisticsSupport::driverPayload($item))->values()->all());
    }

    public function show(int $driver): JsonResponse
    {
        $item = $this->drivers()->where('conductores.id', $driver)->first();

        return $item
            ? ApiResponder::success(LogisticsSupport::driverPayload($item))
            : ApiResponder::error('Conductor no encontrado.', 404);
    }

    public function options(): JsonResponse
    {
        return ApiResponder::success([
            'people' => DB::table('personas')->orderBy('id')->get()->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => trim(implode(' ', array_filter([$item->nombre ?: $item->nombres, $item->apellido_paterno ?: $item->apellidos]))),
            ])->values(),
            'statuses' => DB::table('estado_conductor')->orderBy('nombre')->pluck('nombre')->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'personId' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $statusId = DB::table('estado_conductor')->where('nombre', $payload['status'])->value('id');
        $id = DB::table('conductores')->insertGetId([
            'persona_id' => $payload['personId'] ?: null,
            'name' => $payload['name'],
            'phone' => $payload['phone'] ?? null,
            'status' => $payload['status'],
            'estado_id' => $statusId,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ApiResponder::success([
            'item' => LogisticsSupport::driverPayload($this->drivers()->where('conductores.id', $id)->first()),
            'message' => 'Conductor guardado correctamente.',
        ], 201);
    }

    public function update(Request $request, int $driver): JsonResponse
    {
        $payload = $request->validate([
            'personId' => ['nullable', 'integer'],
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'status' => ['sometimes', 'string', 'max:50'],
        ]);

        $update = ['updated_at' => now()];

        if (array_key_exists('personId', $payload)) {
            $update['persona_id'] = $payload['personId'] ?: null;
        }
        if (array_key_exists('name', $payload)) {
            $update['name'] = $payload['name'];
        }
        if (array_key_exists('phone', $payload)) {
            $update['phone'] = $payload['phone'];
        }
        if (array_key_exists('status', $payload)) {
            $update['status'] = $payload['status'];
            $update['estado_id'] = DB::table('estado_conductor')->where('nombre', $payload['status'])->value('id');
        }

        DB::table('conductores')->where('id', $driver)->update($update);

        return ApiResponder::success([
            'item' => LogisticsSupport::driverPayload($this->drivers()->where('conductores.id', $driver)->first()),
            'message' => 'Conductor actualizado correctamente.',
        ]);
    }

    public function destroy(int $driver): JsonResponse
    {
        DB::table('conductores')->where('id', $driver)->delete();

        return response()->json(null, 204);
    }

    private function drivers()
    {
        return DB::table('conductores')
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
                'personas.telefono',
                'estado_conductor.nombre as estado_nombre',
                DB::raw('COALESCE(turnos_conductor.successful_deliveries, 0) as deliveries_today'),
                DB::raw('CONCAT_WS(" - ", turnos_conductor.start_time, turnos_conductor.end_time) as shift_window'),
            ]);
    }
}