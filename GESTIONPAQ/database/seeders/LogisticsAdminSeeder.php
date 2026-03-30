<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LogisticsAdminSeeder extends Seeder
{
    public function run(): void
    {
        $roleId = (int) (DB::table('roles')->where('nombre', 'admin')->value('id') ?: 1);
        $user = DB::table('usuarios')
            ->whereIn('email', ['admin@gestionpaq.local', 'admin@logistichub.local'])
            ->first();

        if ($user) {
            DB::table('usuarios')->where('id', $user->id)->update([
                'email' => 'admin@gestionpaq.local',
                'password' => Hash::make('admin123'),
                'rol_id' => $roleId,
                'activo' => 1,
                'api_token' => null,
                'last_login_at' => null,
            ]);

            $adminUserId = (int) $user->id;
        } else {
            $adminUserId = (int) DB::table('usuarios')->insertGetId([
                'email' => 'admin@gestionpaq.local',
                'password' => Hash::make('admin123'),
                'rol_id' => $roleId,
                'activo' => 1,
                'api_token' => null,
                'last_login_at' => null,
            ]);
        }

        $person = DB::table('personas')
            ->where('usuario_id', $adminUserId)
            ->orWhereIn('email', ['admin@gestionpaq.local', 'admin@logistichub.local'])
            ->first();

        $payload = [
            'usuario_id' => $adminUserId,
            'nombre' => 'Alicia',
            'apellido_paterno' => 'Ortega',
            'apellido_materno' => 'Mena',
            'nombres' => 'Alicia',
            'apellidos' => 'Ortega Mena',
            'telefono' => '5550000101',
            'documento' => 'ADM-GPQ-001',
            'email' => 'admin@gestionpaq.local',
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($person) {
            DB::table('personas')->where('id', $person->id)->update($payload);

            return;
        }

        DB::table('personas')->insert($payload);
    }
}