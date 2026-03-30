<?php
require __DIR__.'/GESTIONPAQ/vendor/autoload.php';
$app = require __DIR__.'/GESTIONPAQ/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\ShipmentController;
use Illuminate\Http\Request;

$controller = app(ShipmentController::class);
$response = $controller->update(Request::create('/api/shipments/93', 'PUT', []), 93);
echo $response->getContent(), PHP_EOL;
