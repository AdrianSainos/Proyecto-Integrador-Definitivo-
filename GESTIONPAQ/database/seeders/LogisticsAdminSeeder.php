<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LogisticsAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('usuarios')->updateOrInsert(
            ['email' => 'admin@logistichub.local'],
            [
                'password' => Hash::make('admin123'),
                'rol_id' => 1,
                'activo' => 1,
                'api_token' => null,
                'last_login_at' => null,
            ]
        );

        $adminUserId = (int) DB::table('usuarios')->where('email', 'admin@logistichub.local')->value('id');

        DB::table('personas')->updateOrInsert(
            ['usuario_id' => $adminUserId],
            [
                'nombre' => 'Alicia',
                'apellido_paterno' => 'Ortega',
                'telefono' => '5550000101',
                'email' => 'admin@logistichub.local',
                'activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}