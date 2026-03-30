<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackingController extends Controller
{
    public function show(Request $request, string $trackingCode): JsonResponse
    {
        $shipment = LogisticsSupport::shipmentBaseQueryFor($request)
            ->where(function ($query) use ($trackingCode): void {
                $query->where('paquetes.codigo_tracking', $trackingCode)
                    ->orWhere('paquetes.tracking_code', $trackingCode)
                    ->orWhere('paquetes.codigo_rastreo', $trackingCode);
            })
            ->first();

        if (! $shipment) {
            return ApiResponder::error('No se encontro el codigo de rastreo indicado.', 404);
        }

        $events = DB::table('tracking')
            ->leftJoin('estado_paquete', 'estado_paquete.id', '=', 'tracking.estado_id')
            ->where(function ($query) use ($shipment): void {
                $query->where('tracking.paquete_id', $shipment->id)
                    ->orWhere('tracking.package_id', $shipment->id);
            })
            ->orderByRaw('COALESCE(tracking.timestamp_event, tracking.fecha, tracking.created_at)')
            ->get([
                'tracking.id',
                'tracking.event_type',
                'tracking.description',
                'tracking.location',
                'tracking.timestamp_event',
                'tracking.fecha',
                'estado_paquete.nombre as estado_nombre',
            ])
            ->map(fn ($event) => [
                'id' => (int) $event->id,
                'type' => $event->event_type ?: $event->estado_nombre ?: 'Evento',
                'description' => $event->description ?: 'Actualizacion de rastreo',
                'location' => $event->location ?: 'Sin ubicacion',
                'timestamp' => $event->timestamp_event ?: $event->fecha,
            ])
            ->values();

        $evidences = LogisticsSupport::evidenceBaseQuery()
            ->where('evidencias.package_id', $shipment->id)
            ->orderByDesc('evidencias.delivery_timestamp')
            ->get()
            ->map(fn ($item) => LogisticsSupport::evidencePayload($item, $request))
            ->values();

        return ApiResponder::success([
            'shipment' => LogisticsSupport::shipmentPayload($shipment),
            'events' => $events->all(),
            'evidences' => $evidences->all(),
        ]);
    }
}