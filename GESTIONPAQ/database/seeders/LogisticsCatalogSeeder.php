<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LogisticsCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->upsert([
            ['id' => 1, 'nombre' => 'admin'],
            ['id' => 2, 'nombre' => 'operator'],
            ['id' => 3, 'nombre' => 'supervisor'],
            ['id' => 4, 'nombre' => 'dispatcher'],
            ['id' => 5, 'nombre' => 'driver'],
            ['id' => 6, 'nombre' => 'customer'],
        ], ['id'], ['nombre']);

        DB::table('tipo_paquete')->upsert([
            ['id' => 1, 'nombre' => 'Documentacion'],
            ['id' => 2, 'nombre' => 'Medicamento'],
            ['id' => 3, 'nombre' => 'Electronica'],
            ['id' => 4, 'nombre' => 'Carga general'],
        ], ['id'], ['nombre']);

        DB::table('estado_paquete')->upsert([
            ['id' => 1, 'nombre' => 'Pendiente'],
            ['id' => 2, 'nombre' => 'Registrado'],
            ['id' => 3, 'nombre' => 'En ruta'],
            ['id' => 4, 'nombre' => 'Entregado'],
            ['id' => 5, 'nombre' => 'Planificado'],
            ['id' => 6, 'nombre' => 'Asignado'],
        ], ['id'], ['nombre']);

        DB::table('tipo_vehiculo')->upsert([
            ['id' => 1, 'nombre' => 'Van'],
            ['id' => 2, 'nombre' => 'Camion ligero'],
            ['id' => 3, 'nombre' => 'Camion caja seca'],
        ], ['id'], ['nombre']);

        DB::table('estado_vehiculo')->upsert([
            ['id' => 1, 'nombre' => 'Disponible'],
            ['id' => 2, 'nombre' => 'Operativo'],
            ['id' => 3, 'nombre' => 'Mantenimiento'],
        ], ['id'], ['nombre']);

        DB::table('estado_conductor')->upsert([
            ['id' => 1, 'nombre' => 'Disponible'],
            ['id' => 2, 'nombre' => 'En ruta'],
            ['id' => 3, 'nombre' => 'Fuera de turno'],
            ['id' => 4, 'nombre' => 'Activo'],
        ], ['id'], ['nombre']);

        DB::table('estado_ruta')->upsert([
            ['id' => 1, 'nombre' => 'Preparacion'],
            ['id' => 2, 'nombre' => 'En ejecucion'],
            ['id' => 3, 'nombre' => 'Completada'],
            ['id' => 4, 'nombre' => 'Cancelada'],
        ], ['id'], ['nombre']);

        DB::table('tipo_mantenimiento')->upsert([
            ['id' => 1, 'nombre' => 'Preventivo'],
            ['id' => 2, 'nombre' => 'Correctivo'],
            ['id' => 3, 'nombre' => 'Inspeccion'],
            ['id' => 4, 'nombre' => 'Llantas'],
        ], ['id'], ['nombre']);
    }
}
