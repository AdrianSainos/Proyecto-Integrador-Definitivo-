<?php
require __DIR__.'/GESTIONPAQ/vendor/autoload.php';
$app = require __DIR__.'/GESTIONPAQ/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('paquetes')
    ->leftJoin('asignaciones', 'asignaciones.package_id', '=', 'paquetes.id')
    ->whereNull('asignaciones.id')
    ->where(function ($query) {
        $query->where('paquetes.estado', 'Pendiente')->orWhere('paquetes.status', 'Pendiente');
    })
    ->orderBy('paquetes.id')
    ->get(['paquetes.id', 'paquetes.codigo_tracking', 'paquetes.scheduled_date', 'paquetes.origin_warehouse_id']);

var_export($rows->all());
