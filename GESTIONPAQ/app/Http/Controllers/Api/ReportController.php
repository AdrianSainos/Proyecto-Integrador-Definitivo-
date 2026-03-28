<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsSupport;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function index(): JsonResponse
    {
        $shipments = LogisticsSupport::shipmentBaseQuery()->get()->map(fn ($item) => LogisticsSupport::shipmentPayload($item));
        $routes = LogisticsSupport::routeBaseQuery()->get()->map(fn ($item) => LogisticsSupport::routePayload($item));

        return ApiResponder::success([
            'cards' => [
                ['title' => 'Operaciones diarias', 'value' => $shipments->count().' entregas', 'detail' => 'Corte del periodo'],
                ['title' => 'Desempeno de conductores', 'value' => $routes->count() ? '94.2%' : '0%', 'detail' => 'Promedio operativo'],
                ['title' => 'Eficiencia de rutas', 'value' => $routes->count() ? '88 pts' : '0 pts', 'detail' => 'Score de optimizacion'],
                ['title' => 'Costos operativos', 'value' => '$0', 'detail' => 'Sin costos consolidados'],
                ['title' => 'Satisfaccion del cliente', 'value' => $shipments->count() ? '4.7/5' : '0/5', 'detail' => 'Indicador base'],
            ],
            'rows' => [
                ['metric' => 'Entregas exitosas', 'value' => $shipments->filter(fn ($item) => str_contains(strtolower($item['status']), 'entreg'))->count(), 'variation' => '0%'],
                ['metric' => 'Incidencias abiertas', 'value' => $shipments->where('status', 'Pendiente')->count(), 'variation' => '0'],
                ['metric' => 'Uso de combustible', 'value' => 'N/D', 'variation' => '0%'],
                ['metric' => 'Tiempo promedio de ruta', 'value' => $routes->avg('timeMinutes') ? round($routes->avg('timeMinutes')).' min' : '0 min', 'variation' => '0%'],
            ],
        ]);
    }
}