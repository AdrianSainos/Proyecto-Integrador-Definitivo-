<?php

namespace Database\Seeders;

use App\Support\LogisticsSupport;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class LogisticsWorkforceSeeder extends Seeder
{
    public function run(): void
    {
        if (! LogisticsSupport::supportsUsername() || ! LogisticsSupport::supportsPersonnelSchedule()) {
            return;
        }

        $today = CarbonImmutable::today();

        $this->backfillExistingUsers();
        $this->backfillDriverUsers();
        $this->seedAdditionalRoutes($today);
    }

    private function backfillExistingUsers(): void
    {
        $users = DB::table('usuarios')
            ->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')
            ->leftJoin('personas', 'personas.usuario_id', '=', 'usuarios.id')
            ->select([
                'usuarios.id',
                'usuarios.email',
                'usuarios.username',
                'roles.nombre as role_name',
                'personas.id as person_id',
                'personas.nombre',
                'personas.apellido_paterno',
                'personas.apellido_materno',
                'personas.documento',
                'personas.email as person_email',
                'personas.employee_code',
            ])
            ->get();

        foreach ($users as $user) {
            $role = strtolower((string) ($user->role_name ?: 'operator'));
            $isDriver = DB::table('conductores')->where('persona_id', $user->person_id)->exists();
            $profile = $this->defaultProfile($role, $isDriver);
            $username = LogisticsSupport::uniqueUsername($this->usernameSource($user), (int) $user->id);

            DB::table('usuarios')->where('id', $user->id)->update([
                'email' => strtolower((string) $user->email),
                'username' => $username,
            ]);

            if (! $user->person_id) {
                continue;
            }

            DB::table('personas')->where('id', $user->person_id)->update([
                'email' => strtolower((string) $user->email),
                'employee_code' => $user->employee_code ?: $this->defaultEmployeeCode((int) $user->id, $role),
                'job_title' => $profile['jobTitle'],
                'schedule_label' => $profile['scheduleLabel'],
                'work_days' => $profile['workDays'],
                'shift_start' => $profile['shiftStart'].':00',
                'shift_end' => $profile['shiftEnd'].':00',
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillDriverUsers(): void
    {
        $drivers = DB::table('conductores')
            ->leftJoin('personas', 'personas.id', '=', 'conductores.persona_id')
            ->leftJoin('usuarios', 'usuarios.id', '=', 'personas.usuario_id')
            ->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')
            ->select([
                'conductores.id',
                'conductores.persona_id',
                'conductores.name',
                'conductores.email',
                'conductores.phone',
                'conductores.status',
                'personas.usuario_id',
                'personas.nombre',
                'personas.apellido_paterno',
                'personas.apellido_materno',
                'personas.documento',
                'personas.email as person_email',
                'usuarios.email as user_email',
                'usuarios.username',
                'roles.nombre as role_name',
            ])
            ->get();

        foreach ($drivers as $driver) {
            $username = LogisticsSupport::uniqueUsername($this->driverUsernameSource($driver), $driver->usuario_id ? (int) $driver->usuario_id : null);
            $profile = $this->defaultProfile('driver', true);
            $userId = $driver->usuario_id ? (int) $driver->usuario_id : null;
            $managedDriverAccount = ! $userId || strtolower((string) ($driver->role_name ?? '')) === 'driver';
            $email = $managedDriverAccount ? $this->driverEmail($driver, $username) : strtolower((string) ($driver->user_email ?: $driver->person_email ?: ''));

            if (! $userId) {
                $userId = (int) DB::table('usuarios')->insertGetId([
                    'email' => $email,
                    'username' => $username,
                    'password' => Hash::make($this->generatedPasswordFor($username)),
                    'rol_id' => LogisticsSupport::roleIdFor('driver'),
                    'activo' => 1,
                    'api_token' => null,
                    'remember_token' => null,
                    'last_login_at' => null,
                ]);
            } else {
                $userRoleId = (int) DB::table('usuarios')->where('id', $userId)->value('rol_id');
                $roleName = strtolower((string) DB::table('roles')->where('id', $userRoleId)->value('nombre'));

                $userUpdate = [
                    'username' => $username,
                    'email' => $roleName === 'driver' ? $email : (string) ($driver->user_email ?: $email),
                ];

                if ($roleName === 'driver' && strtolower((string) ($driver->user_email ?? '')) !== 'driver@gestionpaq.local') {
                    $userUpdate['password'] = Hash::make($this->generatedPasswordFor($username));
                }

                DB::table('usuarios')->where('id', $userId)->update($userUpdate);
            }

            DB::table('personas')->where('id', $driver->persona_id)->update([
                'usuario_id' => $userId,
                'email' => $email,
                'employee_code' => $this->defaultEmployeeCode($userId, 'driver'),
                'job_title' => $profile['jobTitle'],
                'schedule_label' => $profile['scheduleLabel'],
                'work_days' => $profile['workDays'],
                'shift_start' => $profile['shiftStart'].':00',
                'shift_end' => $profile['shiftEnd'].':00',
                'updated_at' => now(),
            ]);

            DB::table('conductores')->where('id', $driver->id)->update([
                'email' => $email,
                'phone' => $driver->phone ?: null,
                'updated_at' => now(),
            ]);
        }
    }

    private function seedAdditionalRoutes(CarbonImmutable $today): void
    {
        $warehouses = DB::table('almacenes')->get()->keyBy('code');
        $routeStatuses = DB::table('estado_ruta')->pluck('id', 'nombre');
        $drivers = DB::table('conductores')
            ->leftJoin('personas', 'personas.id', '=', 'conductores.persona_id')
            ->leftJoin('usuarios', 'usuarios.id', '=', 'personas.usuario_id')
            ->select([
                'conductores.id',
                'conductores.name',
                'conductores.status',
                'conductores.current_vehicle_id',
                'personas.email as person_email',
                'usuarios.username',
            ])
            ->get();

        $vehicles = DB::table('vehiculos')->get()->keyBy('id');
        $warehouseVehicles = DB::table('vehiculos')
            ->whereIn('status', ['Operativo', 'Disponible'])
            ->orderBy('id')
            ->get()
            ->groupBy('warehouse_id');

        $definitions = [
            ['code' => 'GPQ-R-101', 'driver' => 'lucia.herrera', 'warehouse' => 'MTY-CEDIS', 'destination' => 'APD-HUB', 'status' => 'Completada', 'date' => $today->subDays(3), 'start' => '08:10', 'end' => '13:40', 'distance' => 34.8, 'actualDistance' => 36.4, 'time' => 92, 'actualTime' => 181, 'fuel' => 9.8, 'packages' => 11, 'weight' => 702.5, 'notes' => 'Cobertura metropolitana Monterrey-Apodaca con entregas empresariales.', 'stops' => [['label' => 'Parque Industrial Nogalar', 'lat' => 25.75279000, 'lng' => -100.28621000]]],
            ['code' => 'GPQ-R-102', 'driver' => 'daniel.rios', 'warehouse' => 'MTY-CEDIS', 'destination' => 'STC-CROSS', 'status' => 'En ejecucion', 'date' => $today, 'start' => '07:50', 'end' => null, 'distance' => 29.6, 'actualDistance' => 17.4, 'time' => 74, 'actualTime' => 58, 'fuel' => 5.9, 'packages' => 7, 'weight' => 486.2, 'notes' => 'Ruta activa de reparto poniente para clientes de Santa Catarina.', 'stops' => [['label' => 'Parque Industrial FINSA', 'lat' => 25.69063000, 'lng' => -100.45219000]]],
            ['code' => 'GPQ-R-103', 'driver' => 'sofia.salas', 'warehouse' => 'MTY-CEDIS', 'destination' => 'APD-HUB', 'status' => 'Completada', 'date' => $today->subDay(), 'start' => '09:15', 'end' => '14:05', 'distance' => 27.1, 'actualDistance' => 28.0, 'time' => 69, 'actualTime' => 164, 'fuel' => 6.4, 'packages' => 5, 'weight' => 322.0, 'notes' => 'Cobertura de soporte supervisor entre cedis y hub aeropuerto.', 'stops' => [['label' => 'Guadalupe Centro', 'lat' => 25.67654000, 'lng' => -100.25672000]]],
            ['code' => 'GPQ-R-104', 'driver' => 'paola.leal', 'warehouse' => 'APD-HUB', 'destination' => 'MTY-CEDIS', 'status' => 'Preparacion', 'date' => $today->addDay(), 'start' => '08:30', 'end' => '15:30', 'distance' => 31.2, 'actualDistance' => 0.0, 'time' => 80, 'actualTime' => 0, 'fuel' => 0.0, 'packages' => 9, 'weight' => 558.0, 'notes' => 'Ruta planeada de consolidado retail del aeropuerto al centro metropolitano.', 'stops' => [['label' => 'Parque Industrial Milimex', 'lat' => 25.76092000, 'lng' => -100.22085000]]],
            ['code' => 'GPQ-R-105', 'driver' => 'fernando.macias', 'warehouse' => 'CDMX-NORTE', 'destination' => 'QRO-BAJIO', 'status' => 'En ejecucion', 'date' => $today, 'start' => '06:20', 'end' => null, 'distance' => 223.5, 'actualDistance' => 129.6, 'time' => 210, 'actualTime' => 146, 'fuel' => 23.7, 'packages' => 16, 'weight' => 1280.0, 'notes' => 'Salida troncal Vallejo-Queretaro con consolidado de refacciones y farmacia.', 'stops' => [['label' => 'Arco Norte Jilotepec', 'lat' => 19.94791000, 'lng' => -99.52287000]]],
            ['code' => 'GPQ-R-106', 'driver' => 'andrea.sandoval', 'warehouse' => 'CDMX-NORTE', 'destination' => 'PUE-CROSS', 'status' => 'Completada', 'date' => $today->subDay(), 'start' => '07:05', 'end' => '14:45', 'distance' => 138.9, 'actualDistance' => 142.2, 'time' => 165, 'actualTime' => 292, 'fuel' => 17.8, 'packages' => 14, 'weight' => 1114.0, 'notes' => 'Distribucion cerrada hacia Puebla con surtido de temporada.', 'stops' => [['label' => 'San Martin Texmelucan', 'lat' => 19.28505000, 'lng' => -98.43874000]]],
            ['code' => 'GPQ-R-107', 'driver' => 'ismael.ponce', 'warehouse' => 'GDL-ELSALTO', 'destination' => 'QRO-BAJIO', 'status' => 'Preparacion', 'date' => $today->addDay(), 'start' => '07:15', 'end' => '18:10', 'distance' => 341.4, 'actualDistance' => 0.0, 'time' => 295, 'actualTime' => 0, 'fuel' => 0.0, 'packages' => 18, 'weight' => 1362.0, 'notes' => 'Planeacion Bajio con carga consolidada desde El Salto.', 'stops' => [['label' => 'Lagos de Moreno', 'lat' => 21.35819000, 'lng' => -101.92913000]]],
            ['code' => 'GPQ-R-108', 'driver' => 'monica.lozano', 'warehouse' => 'QRO-BAJIO', 'destination' => 'PUE-CROSS', 'status' => 'En ejecucion', 'date' => $today, 'start' => '07:40', 'end' => null, 'distance' => 336.7, 'actualDistance' => 201.3, 'time' => 288, 'actualTime' => 189, 'fuel' => 22.9, 'packages' => 13, 'weight' => 982.0, 'notes' => 'Ruta Bajio-Oriente con compromiso de entrega antes de corte vespertino.', 'stops' => [['label' => 'San Juan del Rio', 'lat' => 20.38705000, 'lng' => -99.99656000]]],
            ['code' => 'GPQ-R-109', 'driver' => 'rafael.montes', 'warehouse' => 'PUE-CROSS', 'destination' => 'CDMX-NORTE', 'status' => 'Completada', 'date' => $today->subDays(2), 'start' => '06:55', 'end' => '13:15', 'distance' => 139.1, 'actualDistance' => 141.0, 'time' => 168, 'actualTime' => 248, 'fuel' => 16.9, 'packages' => 12, 'weight' => 904.0, 'notes' => 'Retorno consolidado Puebla-CDMX con devoluciones y surtido cruzado.', 'stops' => [['label' => 'Ixtapaluca', 'lat' => 19.31687000, 'lng' => -98.88611000]]],
            ['code' => 'GPQ-R-110', 'driver' => 'ximena.chan', 'warehouse' => 'MID-PONIENTE', 'destination' => 'MID-PONIENTE', 'status' => 'Completada', 'date' => $today->subDay(), 'start' => '08:30', 'end' => '12:35', 'distance' => 24.2, 'actualDistance' => 25.6, 'time' => 72, 'actualTime' => 139, 'fuel' => 4.8, 'packages' => 10, 'weight' => 416.0, 'notes' => 'Circuito urbano de Merida con entregas de ultima milla y recolecciones.', 'stops' => [['label' => 'Merida Centro', 'lat' => 20.96737000, 'lng' => -89.62370000]]],
            ['code' => 'GPQ-R-111', 'driver' => 'cesar.ibarra', 'warehouse' => 'TIJ-FRONTERA', 'destination' => 'TIJ-FRONTERA', 'status' => 'Preparacion', 'date' => $today->addDay(), 'start' => '07:00', 'end' => '15:45', 'distance' => 41.8, 'actualDistance' => 0.0, 'time' => 95, 'actualTime' => 0, 'fuel' => 0.0, 'packages' => 9, 'weight' => 501.0, 'notes' => 'Ruta planeada de frontera para reparto local y recoleccion en parque industrial.', 'stops' => [['label' => 'Otay Industrial', 'lat' => 32.53481000, 'lng' => -116.95958000]]],
            ['code' => 'GPQ-R-112', 'driver' => 'gabriela.rosas', 'warehouse' => 'TIJ-FRONTERA', 'destination' => 'TIJ-FRONTERA', 'status' => 'En ejecucion', 'date' => $today, 'start' => '07:35', 'end' => null, 'distance' => 38.9, 'actualDistance' => 20.6, 'time' => 88, 'actualTime' => 64, 'fuel' => 5.6, 'packages' => 8, 'weight' => 448.0, 'notes' => 'Circuito operativo Tijuana con cobertura de ultima milla para zona industrial.', 'stops' => [['label' => 'Mesa de Otay', 'lat' => 32.53187000, 'lng' => -116.94966000]]],
        ];

        foreach ($definitions as $definition) {
            $driver = $drivers->first(function ($row) use ($definition): bool {
                $keys = array_filter([
                    strtolower((string) ($row->username ?? '')),
                    strtolower((string) ($row->person_email ?? '')),
                    strtolower(strstr((string) ($row->person_email ?? ''), '@', true) ?: ''),
                    LogisticsSupport::normalizeUsername((string) ($row->name ?? '')),
                ]);

                return in_array(strtolower($definition['driver']), $keys, true);
            });

            if (! $driver) {
                continue;
            }

            $origin = $warehouses->get($definition['warehouse']);
            $destination = $warehouses->get($definition['destination']);

            if (! $origin || ! $destination) {
                continue;
            }

            $vehicleId = $this->routeVehicleId($driver, $vehicles, $warehouseVehicles, (int) $origin->id);
            $startAt = $definition['start'] ? $definition['date']->setTime((int) substr($definition['start'], 0, 2), (int) substr($definition['start'], 3, 2)) : null;
            $endAt = $definition['end'] ? $definition['date']->setTime((int) substr($definition['end'], 0, 2), (int) substr($definition['end'], 3, 2)) : null;
            $routeId = $this->upsertRoute(
                ['codigo' => $definition['code']],
                [
                    'codigo' => $definition['code'],
                    'route_code' => $definition['code'],
                    'almacen_origen_id' => $origin->id,
                    'origen_almacen_id' => $origin->id,
                    'destino_almacen_id' => $destination->id,
                    'warehouse_id' => $origin->id,
                    'vehicle_id' => $vehicleId,
                    'driver_id' => $driver->id,
                    'scheduled_date' => $definition['date']->toDateString(),
                    'start_time' => $startAt,
                    'end_time' => $endAt,
                    'distancia_km' => $definition['distance'],
                    'tiempo_estimado_min' => $definition['time'],
                    'estimated_distance_km' => $definition['distance'],
                    'actual_distance_km' => $definition['actualDistance'],
                    'estimated_time_minutes' => $definition['time'],
                    'actual_time_minutes' => $definition['actualTime'],
                    'fuel_consumed_liters' => $definition['fuel'],
                    'total_packages' => $definition['packages'],
                    'total_weight_kg' => $definition['weight'],
                    'optimization_score' => $definition['status'] === 'En ejecucion' ? 91.4 : 84.7,
                    'status' => $definition['status'],
                    'estado' => $definition['status'],
                    'estado_id' => $routeStatuses[$definition['status']] ?? null,
                    'waypoints' => json_encode($this->routeWaypoints($origin, $destination, $definition['stops']), JSON_UNESCAPED_SLASHES),
                    'notes' => $definition['notes'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->upsertDriverShift(
                (int) $driver->id,
                $definition['date'],
                $definition['start'],
                $definition['end'],
                $definition['packages'],
                $definition['status'],
                $definition['actualDistance'] ?: $definition['distance']
            );

            $this->syncDriverOperationalStatus((int) $driver->id, $definition['status']);
            $this->upsertRouteStops($routeId, $definition['stops']);
        }
    }

    private function usernameSource(object $row): string
    {
        $known = [
            'admin@gestionpaq.local' => 'alicia.ortega',
            'operator@gestionpaq.local' => 'olga.reyes',
            'supervisor@gestionpaq.local' => 'sofia.salas',
            'dispatcher@gestionpaq.local' => 'diego.lujan',
            'driver@gestionpaq.local' => 'daniel.rios',
            'customer@gestionpaq.local' => 'carla.mendoza',
        ];

        $email = strtolower((string) ($row->email ?? $row->person_email ?? ''));

        if ($email !== '' && isset($known[$email])) {
            return $known[$email];
        }

        $name = trim(implode(' ', array_filter([
            $row->nombre ?? null,
            $row->apellido_paterno ?? null,
            $row->name ?? null,
        ])));

        return $name !== '' ? $name : ($email !== '' ? strstr($email, '@', true) ?: $email : 'usuario');
    }

    private function driverUsernameSource(object $driver): string
    {
        $name = trim((string) ($driver->name ?? ''));

        if ($name !== '') {
            return $name;
        }

        $email = strtolower((string) ($driver->email ?: $driver->person_email ?: ''));

        return $email !== '' ? (strstr($email, '@', true) ?: $email) : 'driver.'.$driver->id;
    }

    private function driverEmail(object $driver, string $username): string
    {
        $existing = strtolower((string) ($driver->user_email ?: $driver->email ?: $driver->person_email ?: ''));

        if ($existing === 'driver@gestionpaq.local') {
            return $existing;
        }

        return $username.'@gestionpaq.local';
    }

    private function generatedPasswordFor(string $username): string
    {
        return 'Gpq2026!'.$username;
    }

    private function defaultProfile(string $role, bool $isDriver): array
    {
        if ($isDriver) {
            return [
                'jobTitle' => 'Conductor de reparto',
                'scheduleLabel' => 'Primera salida',
                'workDays' => 'Lun-Sab',
                'shiftStart' => '07:30',
                'shiftEnd' => '18:00',
            ];
        }

        return match ($role) {
            'admin' => ['jobTitle' => 'Administrador de plataforma', 'scheduleLabel' => 'Jornada administrativa', 'workDays' => 'Lun-Vie', 'shiftStart' => '08:00', 'shiftEnd' => '17:00'],
            'supervisor' => ['jobTitle' => 'Supervisor logistico', 'scheduleLabel' => 'Supervision regional', 'workDays' => 'Lun-Sab', 'shiftStart' => '09:00', 'shiftEnd' => '18:00'],
            'dispatcher' => ['jobTitle' => 'Despachador operativo', 'scheduleLabel' => 'Despacho AM', 'workDays' => 'Lun-Sab', 'shiftStart' => '06:00', 'shiftEnd' => '15:00'],
            'customer' => ['jobTitle' => 'Contacto cliente', 'scheduleLabel' => 'Portal cliente', 'workDays' => 'Lun-Dom', 'shiftStart' => '00:00', 'shiftEnd' => '23:59'],
            default => ['jobTitle' => 'Operador logistico', 'scheduleLabel' => 'Mesa operativa', 'workDays' => 'Lun-Sab', 'shiftStart' => '07:00', 'shiftEnd' => '16:00'],
        };
    }

    private function defaultEmployeeCode(int $subjectId, string $role): string
    {
        $prefix = match ($role) {
            'admin' => 'ADM',
            'supervisor' => 'SUP',
            'dispatcher' => 'DSP',
            'driver' => 'DRV',
            'customer' => 'CLI',
            default => 'OPR',
        };

        return sprintf('%s-%04d', $prefix, $subjectId);
    }

    private function routeVehicleId(object $driver, $vehicles, $warehouseVehicles, int $warehouseId): ?int
    {
        if ($driver->current_vehicle_id && isset($vehicles[$driver->current_vehicle_id])) {
            return (int) $driver->current_vehicle_id;
        }

        $available = $warehouseVehicles->get($warehouseId);

        return $available && $available->count() ? (int) $available->first()->id : null;
    }

    private function routeWaypoints(object $origin, object $destination, array $stops): array
    {
        return array_merge([
            ['label' => $origin->nombre, 'lat' => (float) $origin->latitude, 'lng' => (float) $origin->longitude],
        ], $stops, [
            ['label' => $destination->nombre, 'lat' => (float) $destination->latitude, 'lng' => (float) $destination->longitude],
        ]);
    }

    private function upsertRoute(array $match, array $payload): int
    {
        $existing = DB::table('rutas')->where($match)->first();

        if ($existing) {
            DB::table('rutas')->where('id', $existing->id)->update($payload);

            return (int) $existing->id;
        }

        return (int) DB::table('rutas')->insertGetId($payload);
    }

    private function upsertDriverShift(int $driverId, CarbonImmutable $date, ?string $startTime, ?string $endTime, int $totalDeliveries, string $routeStatus, float $distanceKm): void
    {
        $shiftStatus = match ($routeStatus) {
            'En ejecucion' => 'in_progress',
            'Completada' => 'completed',
            default => 'scheduled',
        };

        $state = match ($routeStatus) {
            'En ejecucion' => 'activo',
            'Completada' => 'cerrado',
            default => 'programado',
        };

        DB::table('turnos_conductor')->updateOrInsert(
            ['driver_id' => $driverId, 'shift_date' => $date->toDateString()],
            [
                'conductor_id' => $driverId,
                'start_time' => $startTime ? $startTime.':00' : null,
                'end_time' => $endTime ? $endTime.':00' : null,
                'total_deliveries' => $totalDeliveries,
                'successful_deliveries' => $routeStatus === 'Completada' ? $totalDeliveries : max(0, $totalDeliveries - 2),
                'failed_deliveries' => $routeStatus === 'Completada' ? 0 : min(1, $totalDeliveries),
                'distance_km' => $distanceKm,
                'status' => $shiftStatus,
                'inicio_turno' => $startTime ? $date->setTime((int) substr($startTime, 0, 2), (int) substr($startTime, 3, 2)) : $date->setTime(7, 0),
                'fin_turno' => $endTime ? $date->setTime((int) substr($endTime, 0, 2), (int) substr($endTime, 3, 2)) : null,
                'estado' => $state,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function syncDriverOperationalStatus(int $driverId, string $routeStatus): void
    {
        $status = match ($routeStatus) {
            'En ejecucion' => 'En ruta',
            'Preparacion' => 'Activo',
            default => 'Disponible',
        };

        $statusId = DB::table('estado_conductor')->where('nombre', $status)->value('id');

        DB::table('conductores')->where('id', $driverId)->update([
            'status' => $status,
            'estado_id' => $statusId,
            'last_seen_at' => now()->subMinutes(rand(3, 28)),
            'updated_at' => now(),
        ]);
    }

    private function upsertRouteStops(int $routeId, array $stops): void
    {
        if (! Schema::hasTable('ruta_paradas')) {
            return;
        }

        foreach ($stops as $index => $stop) {
            DB::table('ruta_paradas')->updateOrInsert(
                ['ruta_id' => $routeId, 'orden' => $index + 1],
                [
                    'ruta_id' => $routeId,
                    'route_id' => $routeId,
                    'orden' => $index + 1,
                    'stop_number' => $index + 1,
                    'type' => 'checkpoint',
                    'latitude' => $stop['lat'],
                    'longitude' => $stop['lng'],
                    'address' => $stop['label'],
                    'city' => null,
                    'state' => null,
                    'status' => 'planned',
                    'notes' => $stop['label'],
                    'meta' => json_encode($stop, JSON_UNESCAPED_SLASHES),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}