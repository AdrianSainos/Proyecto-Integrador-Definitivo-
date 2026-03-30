<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
foreach (['almacenes', 'clientes', 'vehiculos', 'conductores'] as $table) {
    echo $table . ': ' . Illuminate\Support\Facades\DB::table($table)->count() . PHP_EOL;
}
