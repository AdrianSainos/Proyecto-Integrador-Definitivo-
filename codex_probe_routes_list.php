<?php
require __DIR__.'/GESTIONPAQ/vendor/autoload.php';
$app = require __DIR__.'/GESTIONPAQ/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\ShipmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$controller = app(ShipmentController::class);
$shipmentIds = DB::table('paquetes')
    ->leftJoin('asignaciones', 'asignaciones.package_id', '=', 'paquetes.id')
    ->whereNull('asignaciones.id')
    ->where(function ($query) {
        $query->where('paquetes.estado', 'Pendiente')->orWhere('paquetes.status', 'Pendiente');
    })
    ->pluck('paquetes.id');

$result = [];
foreach ($shipmentIds as $shipmentId) {
    $response = $controller->update(Request::create('/api/shipments/'.$shipmentId, 'PUT', []), (int) $shipmentId);
    $payload = json_decode($response->getContent(), true);
    $result[] = [
        'id' => (int) $shipmentId,
        'status' => $payload['item']['status'] ?? null,
        'routeCode' => $payload['item']['routeCode'] ?? null,
        'driverName' => $payload['item']['driverName'] ?? null,
        'message' => $payload['message'] ?? null,
    ];
}

var_export($result);
