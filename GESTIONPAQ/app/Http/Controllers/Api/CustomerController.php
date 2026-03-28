<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(): JsonResponse
    {
        $customers = DB::table('clientes')
            ->leftJoin('personas', 'personas.id', '=', 'clientes.persona_id')
            ->select([
                'clientes.*',
                'personas.nombre',
                'personas.apellido_paterno',
                'personas.telefono',
            ])
            ->orderBy('clientes.id')
            ->get()
            ->map(function ($item) {
                $payload = LogisticsSupport::customerPayload($item);
                $payload['addresses'] = DB::table('cliente_direcciones')
                    ->where('cliente_direcciones.cliente_id', $item->id)
                    ->orderByDesc('cliente_direcciones.is_default')
                    ->orderBy('cliente_direcciones.label')
                    ->get()
                    ->map(fn ($address) => [
                        'id' => (int) $address->id,
                        'label' => $address->label,
                        'address' => $address->address,
                        'city' => $address->city,
                        'state' => $address->state,
                        'postalCode' => $address->postal_code,
                        'latitude' => $address->latitude,
                        'longitude' => $address->longitude,
                    ])
                    ->values();

                return $payload;
            })
            ->values();

        return ApiResponder::success($customers->all());
    }
}