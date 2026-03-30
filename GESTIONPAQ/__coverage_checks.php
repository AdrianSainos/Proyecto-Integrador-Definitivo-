<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$checks = [
    ['almacenes', 'codigo', 'ALM-GPQ-CDMX'],
    ['almacenes', 'codigo', 'ALM-GPQ-TIJ'],
    ['clientes', 'codigo_cliente', 'CLI-GPQ-012'],
    ['clientes', 'codigo_cliente', 'CLI-GPQ-017'],
    ['vehiculos', 'placa', 'GPQ-118-MX'],
    ['vehiculos', 'placa', 'GPQ-612-BC'],
    ['personas', 'email', 'fernando.macias@gestionpaq.local'],
    ['personas', 'email', 'gabriela.rosas@gestionpaq.local'],
];
foreach ($checks as [$table, $column, $needle]) {
    echo $table . '/' . $needle . ': ' . Illuminate\Support\Facades\DB::table($table)->where($column, $needle)->count() . PHP_EOL;
}
