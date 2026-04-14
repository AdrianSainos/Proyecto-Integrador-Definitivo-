<?php

namespace Database\Seeders;

use App\Support\LogisticsPlanner;
use App\Support\LogisticsSupport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LogisticsDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $today = $now->copy()->startOfDay();
            $yesterday = $today->copy()->subDay();
            $twoDaysAgo = $today->copy()->subDays(2);
            $threeDaysAgo = $today->copy()->subDays(3);
            $tomorrow = $today->copy()->addDay();

            DB::table('configuracion_sistema')->upsert([
                [
                    'clave' => 'companyName',
                    'valor' => 'GESTIONPAQ',
                    'tipo' => 'string',
                    'grupo' => 'general',
                    'etiqueta' => 'Nombre de la empresa',
                    'descripcion' => 'Nombre comercial mostrado en tableros y reportes.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'clave' => 'supportEmail',
                    'valor' => 'soporte@gestionpaq.mx',
                    'tipo' => 'string',
                    'grupo' => 'general',
                    'etiqueta' => 'Email de soporte',
                    'descripcion' => 'Canal operativo para incidencias y acompanamiento.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'clave' => 'supportPhone',
                    'valor' => '555-000-4455',
                    'tipo' => 'string',
                    'grupo' => 'general',
                    'etiqueta' => 'Telefono de soporte',
                    'descripcion' => 'Telefono de respaldo para coordinacion de ultima milla.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'clave' => 'dispatchStartTime',
                    'valor' => '06:30',
                    'tipo' => 'time',
                    'grupo' => 'operacion',
                    'etiqueta' => 'Inicio de despacho',
                    'descripcion' => 'Hora sugerida para el arranque operativo diario.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'clave' => 'defaultLeadDays',
                    'valor' => '2',
                    'tipo' => 'number',
                    'grupo' => 'operacion',
                    'etiqueta' => 'Lead time por defecto',
                    'descripcion' => 'Dias de compromiso para nuevas solicitudes.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'clave' => 'maxDeliveryAttempts',
                    'valor' => '3',
                    'tipo' => 'number',
                    'grupo' => 'operacion',
                    'etiqueta' => 'Intentos maximos',
                    'descripcion' => 'Cantidad maxima de intentos antes de escalar el envio.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'clave' => 'requirePhoto',
                    'valor' => '1',
                    'tipo' => 'boolean',
                    'grupo' => 'evidencia',
                    'etiqueta' => 'Foto obligatoria',
                    'descripcion' => 'La prueba de entrega requiere fotografia.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'clave' => 'requireSignature',
                    'valor' => '1',
                    'tipo' => 'boolean',
                    'grupo' => 'evidencia',
                    'etiqueta' => 'Firma obligatoria',
                    'descripcion' => 'La prueba de entrega requiere firma.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ], ['clave'], ['valor', 'tipo', 'grupo', 'etiqueta', 'descripcion', 'updated_at']);

            $roles = DB::table('roles')->pluck('id', 'nombre');
            $packageStatuses = DB::table('estado_paquete')->pluck('id', 'nombre');
            $routeStatuses = DB::table('estado_ruta')->pluck('id', 'nombre');
            $vehicleStatuses = DB::table('estado_vehiculo')->pluck('id', 'nombre');
            $driverStatuses = DB::table('estado_conductor')->pluck('id', 'nombre');
            $vehicleTypes = DB::table('tipo_vehiculo')->pluck('id', 'nombre');
            $packageTypes = DB::table('tipo_paquete')->pluck('id', 'nombre');
            $maintenanceTypes = DB::table('tipo_mantenimiento')->pluck('id', 'nombre');

            $users = [
                'operator' => [
                    'email' => 'operator@gestionpaq.local',
                    'legacyEmail' => 'operator@logistichub.local',
                    'password' => 'oper123',
                    'role' => 'operator',
                    'person' => [
                        'nombre' => 'Olga',
                        'apellido_paterno' => 'Reyes',
                        'apellido_materno' => 'Torres',
                        'telefono' => '5550000102',
                        'documento' => 'OPE-GPQ-001',
                    ],
                ],
                'supervisor' => [
                    'email' => 'supervisor@gestionpaq.local',
                    'legacyEmail' => 'supervisor@logistichub.local',
                    'password' => 'super123',
                    'role' => 'supervisor',
                    'person' => [
                        'nombre' => 'Sofia',
                        'apellido_paterno' => 'Salas',
                        'apellido_materno' => 'Gomez',
                        'telefono' => '5550000103',
                        'documento' => 'SUP-GPQ-001',
                    ],
                ],
                'dispatcher' => [
                    'email' => 'dispatcher@gestionpaq.local',
                    'legacyEmail' => 'dispatcher@logistichub.local',
                    'password' => 'dispatch123',
                    'role' => 'dispatcher',
                    'person' => [
                        'nombre' => 'Diego',
                        'apellido_paterno' => 'Lujan',
                        'apellido_materno' => 'Serrano',
                        'telefono' => '5550000104',
                        'documento' => 'DSP-GPQ-001',
                    ],
                ],
                'driver' => [
                    'email' => 'driver@gestionpaq.local',
                    'legacyEmail' => 'driver@logistichub.local',
                    'password' => 'driver123',
                    'role' => 'driver',
                    'person' => [
                        'nombre' => 'Daniel',
                        'apellido_paterno' => 'Rios',
                        'apellido_materno' => 'Flores',
                        'telefono' => '5550000105',
                        'documento' => 'DRV-GPQ-001',
                    ],
                ],
                'customer' => [
                    'email' => 'customer@gestionpaq.local',
                    'legacyEmail' => 'customer@logistichub.local',
                    'password' => 'client123',
                    'role' => 'customer',
                    'person' => [
                        'nombre' => 'Carla',
                        'apellido_paterno' => 'Mendoza',
                        'apellido_materno' => 'Lopez',
                        'telefono' => '5550000106',
                        'documento' => 'CLI-GPQ-001',
                    ],
                ],
            ];

            $userIds = [];
            $personIds = [];

            foreach ($users as $key => $definition) {
                $userIds[$key] = $this->upsertUser(
                    $definition['email'],
                    $definition['legacyEmail'],
                    (int) $roles[$definition['role']],
                    $definition['password']
                );

                $personIds[$key] = $this->upsertLinkedPerson(
                    $userIds[$key],
                    $definition['email'],
                    $definition['legacyEmail'],
                    $definition['person']
                );
            }

            $farmaciaContactId = $this->upsertStandalonePerson('operaciones@farmaciacentro.mx', [
                'nombre' => 'Mariana',
                'apellido_paterno' => 'Paredes',
                'apellido_materno' => 'Ibarra',
                'telefono' => '8187001122',
                'documento' => 'CLT-GPQ-002',
            ]);
            $textilesContactId = $this->upsertStandalonePerson('logistica@textilesoriente.mx', [
                'nombre' => 'Rafael',
                'apellido_paterno' => 'Lozano',
                'apellido_materno' => 'Perez',
                'telefono' => '8187001133',
                'documento' => 'CLT-GPQ-003',
            ]);
            $backupDriverPersonId = $this->upsertStandalonePerson('lucia.herrera@gestionpaq.local', [
                'nombre' => 'Lucia',
                'apellido_paterno' => 'Herrera',
                'apellido_materno' => 'Soto',
                'telefono' => '8187001144',
                'documento' => 'DRV-GPQ-002',
            ]);

            $addressIds = [
                'warehouseMty' => $this->upsertAddress('Cedis Monterrey', [
                    'calle' => 'Av. Industria',
                    'numero' => '2450',
                    'colonia' => 'Parque Industrial Escobedo',
                    'ciudad' => 'Monterrey',
                    'estado' => 'Nuevo Leon',
                    'codigo_postal' => '66052',
                    'numero_ext' => '2450',
                    'latitud' => 25.79456000,
                    'longitud' => -100.31487000,
                ]),
                'warehouseApodaca' => $this->upsertAddress('Hub Apodaca', [
                    'calle' => 'Carretera Miguel Aleman',
                    'numero' => '980',
                    'colonia' => 'Parque Stiva',
                    'ciudad' => 'Apodaca',
                    'estado' => 'Nuevo Leon',
                    'codigo_postal' => '66603',
                    'numero_ext' => '980',
                    'latitud' => 25.77943000,
                    'longitud' => -100.18641000,
                ]),
                'carlaHome' => $this->upsertAddress('Carla Mendoza - Casa', [
                    'calle' => 'Paseo del Acueducto',
                    'numero' => '118',
                    'colonia' => 'Cumbres Elite',
                    'ciudad' => 'Monterrey',
                    'estado' => 'Nuevo Leon',
                    'codigo_postal' => '64349',
                    'numero_ext' => '118',
                    'latitud' => 25.73654000,
                    'longitud' => -100.36029000,
                ]),
                'carlaOffice' => $this->upsertAddress('Carla Mendoza - Oficina', [
                    'calle' => 'Av. Revolucion',
                    'numero' => '4020',
                    'colonia' => 'Contry',
                    'ciudad' => 'Monterrey',
                    'estado' => 'Nuevo Leon',
                    'codigo_postal' => '64860',
                    'numero_ext' => '4020',
                    'latitud' => 25.65114000,
                    'longitud' => -100.27856000,
                ]),
                'farmaciaMain' => $this->upsertAddress('Farmacia Centro - Recepcion', [
                    'calle' => 'Av. Sendero',
                    'numero' => '810',
                    'colonia' => 'Residencial Anahuac',
                    'ciudad' => 'San Nicolas',
                    'estado' => 'Nuevo Leon',
                    'codigo_postal' => '66457',
                    'numero_ext' => '810',
                    'latitud' => 25.74203000,
                    'longitud' => -100.30210000,
                ]),
                'textilesMain' => $this->upsertAddress('Textiles Oriente - Recibo', [
                    'calle' => 'Av. Miguel de la Madrid',
                    'numero' => '1501',
                    'colonia' => 'Nueva Linda Vista',
                    'ciudad' => 'Guadalupe',
                    'estado' => 'Nuevo Leon',
                    'codigo_postal' => '67130',
                    'numero_ext' => '1501',
                    'latitud' => 25.68802000,
                    'longitud' => -100.20831000,
                ]),
            ];

            $clientIds = [
                'carla' => $this->upsertClient('CLI-GPQ-001', $personIds['customer'], [
                    'name' => 'Carla Mendoza',
                    'email' => 'customer@gestionpaq.local',
                    'phone' => '5550000106',
                    'identification' => 'CLI-GPQ-001',
                    'type' => 'individual',
                    'status' => 'active',
                    'default_address' => 'Paseo del Acueducto 118, Cumbres Elite, Monterrey, Nuevo Leon',
                    'latitude' => 25.73654000,
                    'longitude' => -100.36029000,
                    'notes' => 'Cuenta demo para el portal cliente de GESTIONPAQ.',
                    'nivel_servicio' => 'premium',
                    'activo' => 1,
                ]),
                'farmacia' => $this->upsertClient('CLI-GPQ-002', $farmaciaContactId, [
                    'name' => 'Farmacia Centro',
                    'email' => 'operaciones@farmaciacentro.mx',
                    'phone' => '8187001122',
                    'identification' => 'CLI-GPQ-002',
                    'type' => 'business',
                    'status' => 'active',
                    'default_address' => 'Av. Sendero 810, Residencial Anahuac, San Nicolas, Nuevo Leon',
                    'latitude' => 25.74203000,
                    'longitude' => -100.30210000,
                    'notes' => 'Cliente corporativo de prueba para operaciones farmaceuticas.',
                    'nivel_servicio' => 'corporativo',
                    'activo' => 1,
                ]),
                'textiles' => $this->upsertClient('CLI-GPQ-003', $textilesContactId, [
                    'name' => 'Textiles Oriente',
                    'email' => 'logistica@textilesoriente.mx',
                    'phone' => '8187001133',
                    'identification' => 'CLI-GPQ-003',
                    'type' => 'business',
                    'status' => 'active',
                    'default_address' => 'Av. Miguel de la Madrid 1501, Nueva Linda Vista, Guadalupe, Nuevo Leon',
                    'latitude' => 25.68802000,
                    'longitude' => -100.20831000,
                    'notes' => 'Cliente corporativo de textiles para cobertura metropolitana.',
                    'nivel_servicio' => 'estandar',
                    'activo' => 1,
                ]),
            ];

            $customerAddressIds = [
                'carlaHome' => $this->upsertCustomerAddress($clientIds['carla'], 'principal', [
                    'direccion_id' => $addressIds['carlaHome'],
                    'address' => 'Paseo del Acueducto 118',
                    'city' => 'Monterrey',
                    'state' => 'Nuevo Leon',
                    'postal_code' => '64349',
                    'latitude' => 25.73654000,
                    'longitude' => -100.36029000,
                    'is_default' => 1,
                ]),
                'carlaOffice' => $this->upsertCustomerAddress($clientIds['carla'], 'oficina', [
                    'direccion_id' => $addressIds['carlaOffice'],
                    'address' => 'Av. Revolucion 4020',
                    'city' => 'Monterrey',
                    'state' => 'Nuevo Leon',
                    'postal_code' => '64860',
                    'latitude' => 25.65114000,
                    'longitude' => -100.27856000,
                    'is_default' => 0,
                ]),
                'farmaciaMain' => $this->upsertCustomerAddress($clientIds['farmacia'], 'principal', [
                    'direccion_id' => $addressIds['farmaciaMain'],
                    'address' => 'Av. Sendero 810',
                    'city' => 'San Nicolas',
                    'state' => 'Nuevo Leon',
                    'postal_code' => '66457',
                    'latitude' => 25.74203000,
                    'longitude' => -100.30210000,
                    'is_default' => 1,
                ]),
                'textilesMain' => $this->upsertCustomerAddress($clientIds['textiles'], 'principal', [
                    'direccion_id' => $addressIds['textilesMain'],
                    'address' => 'Av. Miguel de la Madrid 1501',
                    'city' => 'Guadalupe',
                    'state' => 'Nuevo Leon',
                    'postal_code' => '67130',
                    'latitude' => 25.68802000,
                    'longitude' => -100.20831000,
                    'is_default' => 1,
                ]),
            ];

            $warehouseIds = [
                'mty' => $this->upsertAndGetId('almacenes', ['codigo' => 'ALM-GPQ-MTY'], [
                    'nombre' => 'Cedis Monterrey',
                    'code' => 'MTY-CEDIS',
                    'direccion_id' => $addressIds['warehouseMty'],
                    'address' => 'Av. Industria 2450',
                    'city' => 'Monterrey',
                    'state' => 'Nuevo Leon',
                    'postal_code' => '66052',
                    'latitude' => 25.79456000,
                    'longitude' => -100.31487000,
                    'capacity' => 1200,
                    'status' => 'active',
                    'activo' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
                'apodaca' => $this->upsertAndGetId('almacenes', ['codigo' => 'ALM-GPQ-APD'], [
                    'nombre' => 'Hub Apodaca',
                    'code' => 'APD-HUB',
                    'direccion_id' => $addressIds['warehouseApodaca'],
                    'address' => 'Carretera Miguel Aleman 980',
                    'city' => 'Apodaca',
                    'state' => 'Nuevo Leon',
                    'postal_code' => '66603',
                    'latitude' => 25.77943000,
                    'longitude' => -100.18641000,
                    'capacity' => 800,
                    'status' => 'active',
                    'activo' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            ];

            $vehicleIds = [
                'sprinter' => $this->upsertAndGetId('vehiculos', ['placa' => 'GPQ-231-A'], [
                    'warehouse_id' => $warehouseIds['mty'],
                    'plate' => 'GPQ-231-A',
                    'model' => 'Sprinter 311',
                    'brand' => 'Mercedes-Benz',
                    'year' => 2023,
                    'type' => 'Van',
                    'capacity_kg' => 900,
                    'capacity_packages' => 42,
                    'current_fuel' => 62.0,
                    'fuel_capacity' => 80.0,
                    'fuel_consumption_km' => 0.1080,
                    'status' => 'Operativo',
                    'last_maintenance' => $today->copy()->subDays(18)->setTime(14, 0),
                    'total_km' => 18450.0,
                    'latitude' => 25.76831000,
                    'longitude' => -100.30052000,
                    'vin' => 'MBGPQSPRINTER231A',
                    'tipo_id' => (int) $vehicleTypes['Van'],
                    'capacidad' => 900.0,
                    'capacidad_kg' => 900,
                    'estado_id' => (int) $vehicleStatuses['Operativo'],
                    'estado' => 'Operativo',
                    'activo' => 1,
                    'consumo_km' => 0.11,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
                'boxTruck' => $this->upsertAndGetId('vehiculos', ['placa' => 'GPQ-745-B'], [
                    'warehouse_id' => $warehouseIds['mty'],
                    'plate' => 'GPQ-745-B',
                    'model' => 'Forward 800',
                    'brand' => 'Isuzu',
                    'year' => 2022,
                    'type' => 'Camion ligero',
                    'capacity_kg' => 2500,
                    'capacity_packages' => 90,
                    'current_fuel' => 88.0,
                    'fuel_capacity' => 110.0,
                    'fuel_consumption_km' => 0.1570,
                    'status' => 'Disponible',
                    'last_maintenance' => $today->copy()->subDays(9)->setTime(11, 30),
                    'total_km' => 26380.0,
                    'latitude' => 25.79211000,
                    'longitude' => -100.31201000,
                    'vin' => 'ISUGPQFORWARD745B',
                    'tipo_id' => (int) $vehicleTypes['Camion ligero'],
                    'capacidad' => 2500.0,
                    'capacidad_kg' => 2500,
                    'estado_id' => (int) $vehicleStatuses['Disponible'],
                    'estado' => 'Disponible',
                    'activo' => 1,
                    'consumo_km' => 0.16,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
                'maintenanceVan' => $this->upsertAndGetId('vehiculos', ['placa' => 'GPQ-118-C'], [
                    'warehouse_id' => $warehouseIds['apodaca'],
                    'plate' => 'GPQ-118-C',
                    'model' => 'Transit Custom',
                    'brand' => 'Ford',
                    'year' => 2021,
                    'type' => 'Van',
                    'capacity_kg' => 800,
                    'capacity_packages' => 38,
                    'current_fuel' => 18.0,
                    'fuel_capacity' => 72.0,
                    'fuel_consumption_km' => 0.1180,
                    'status' => 'Mantenimiento',
                    'last_maintenance' => $today->copy()->subDays(40)->setTime(9, 0),
                    'total_km' => 31820.0,
                    'latitude' => 25.77943000,
                    'longitude' => -100.18641000,
                    'vin' => 'FORDGPQTRANSIT118C',
                    'tipo_id' => (int) $vehicleTypes['Van'],
                    'capacidad' => 800.0,
                    'capacidad_kg' => 800,
                    'estado_id' => (int) $vehicleStatuses['Mantenimiento'],
                    'estado' => 'Mantenimiento',
                    'activo' => 1,
                    'consumo_km' => 0.12,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            ];

            $driverIds = [
                'daniel' => $this->upsertDriver($personIds['driver'], [
                    'numero_licencia' => 'LIC-GPQ-1001',
                    'licencia_vence' => '2027-11-30',
                    'activo' => 1,
                    'estado_id' => (int) $driverStatuses['En ruta'],
                    'name' => 'Daniel Rios',
                    'email' => 'driver@gestionpaq.local',
                    'phone' => '5550000105',
                    'license_number' => 'LIC-GPQ-1001',
                    'license_expiry' => '2027-11-30',
                    'identification' => 'DRV-GPQ-001',
                    'date_of_birth' => '1992-03-14',
                    'address' => 'Cumbres Elite, Monterrey, Nuevo Leon',
                    'status' => 'En ruta',
                    'current_vehicle_id' => $vehicleIds['sprinter'],
                    'latitude' => 25.73411000,
                    'longitude' => -100.30452000,
                    'last_seen_at' => $now->copy()->subMinutes(8),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
                'lucia' => $this->upsertDriver($backupDriverPersonId, [
                    'numero_licencia' => 'LIC-GPQ-1002',
                    'licencia_vence' => '2026-08-18',
                    'activo' => 1,
                    'estado_id' => (int) $driverStatuses['Fuera de turno'],
                    'name' => 'Lucia Herrera',
                    'email' => 'lucia.herrera@gestionpaq.local',
                    'phone' => '8187001144',
                    'license_number' => 'LIC-GPQ-1002',
                    'license_expiry' => '2026-08-18',
                    'identification' => 'DRV-GPQ-002',
                    'date_of_birth' => '1988-09-22',
                    'address' => 'San Nicolas, Nuevo Leon',
                    'status' => 'Fuera de turno',
                    'current_vehicle_id' => $vehicleIds['boxTruck'],
                    'latitude' => 25.74203000,
                    'longitude' => -100.30210000,
                    'last_seen_at' => $today->copy()->setTime(7, 5),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            ];

            DB::table('turnos_conductor')->updateOrInsert(
                ['driver_id' => $driverIds['daniel'], 'shift_date' => $today->toDateString()],
                [
                    'conductor_id' => $driverIds['daniel'],
                    'start_time' => '08:00:00',
                    'end_time' => '18:00:00',
                    'total_deliveries' => 6,
                    'successful_deliveries' => 5,
                    'failed_deliveries' => 1,
                    'distance_km' => 72.4,
                    'status' => 'in_progress',
                    'inicio_turno' => $today->copy()->setTime(8, 0),
                    'fin_turno' => null,
                    'estado' => 'activo',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            DB::table('turnos_conductor')->updateOrInsert(
                ['driver_id' => $driverIds['lucia'], 'shift_date' => $today->toDateString()],
                [
                    'conductor_id' => $driverIds['lucia'],
                    'start_time' => '07:00:00',
                    'end_time' => '15:00:00',
                    'total_deliveries' => 2,
                    'successful_deliveries' => 2,
                    'failed_deliveries' => 0,
                    'distance_km' => 18.0,
                    'status' => 'scheduled',
                    'inicio_turno' => $today->copy()->setTime(7, 0),
                    'fin_turno' => $today->copy()->setTime(15, 5),
                    'estado' => 'cerrado',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $routeIds = [
                'active' => $this->upsertAndGetId('rutas', ['codigo' => 'GPQ-R-001'], [
                    'almacen_origen_id' => $warehouseIds['mty'],
                    'origen_almacen_id' => $warehouseIds['mty'],
                    'destino_almacen_id' => $warehouseIds['apodaca'],
                    'distancia_km' => 38.4,
                    'tiempo_estimado_min' => 95,
                    'estado_id' => (int) $routeStatuses['En ejecucion'],
                    'route_code' => 'GPQ-R-001',
                    'vehicle_id' => $vehicleIds['sprinter'],
                    'driver_id' => $driverIds['daniel'],
                    'warehouse_id' => $warehouseIds['mty'],
                    'scheduled_date' => $today->toDateString(),
                    'start_time' => $now->copy()->subHours(3),
                    'end_time' => null,
                    'total_packages' => 0,
                    'total_weight_kg' => 0,
                    'estimated_distance_km' => 38.4,
                    'actual_distance_km' => 21.8,
                    'estimated_time_minutes' => 95,
                    'actual_time_minutes' => 68,
                    'fuel_consumed_liters' => 9.7,
                    'status' => 'En ejecucion',
                    'optimization_score' => 0,
                    'waypoints' => json_encode([
                        ['label' => 'Cedis Monterrey', 'lat' => 25.79456, 'lng' => -100.31487],
                        ['label' => 'San Nicolas', 'lat' => 25.74203, 'lng' => -100.30210],
                        ['label' => 'Guadalupe', 'lat' => 25.68802, 'lng' => -100.20831],
                    ], JSON_UNESCAPED_SLASHES),
                    'notes' => 'Ruta activa del dataset demo GESTIONPAQ.',
                    'estado' => 'En ejecucion',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
                'planned' => $this->upsertAndGetId('rutas', ['codigo' => 'GPQ-R-002'], [
                    'almacen_origen_id' => $warehouseIds['mty'],
                    'origen_almacen_id' => $warehouseIds['mty'],
                    'destino_almacen_id' => $warehouseIds['apodaca'],
                    'distancia_km' => 29.1,
                    'tiempo_estimado_min' => 80,
                    'estado_id' => (int) $routeStatuses['Preparacion'],
                    'route_code' => 'GPQ-R-002',
                    'vehicle_id' => $vehicleIds['boxTruck'],
                    'driver_id' => $driverIds['lucia'],
                    'warehouse_id' => $warehouseIds['mty'],
                    'scheduled_date' => $tomorrow->toDateString(),
                    'start_time' => null,
                    'end_time' => null,
                    'total_packages' => 0,
                    'total_weight_kg' => 0,
                    'estimated_distance_km' => 29.1,
                    'actual_distance_km' => 0,
                    'estimated_time_minutes' => 80,
                    'actual_time_minutes' => 0,
                    'fuel_consumed_liters' => 0,
                    'status' => 'Preparacion',
                    'optimization_score' => 0,
                    'waypoints' => json_encode([
                        ['label' => 'Cedis Monterrey', 'lat' => 25.79456, 'lng' => -100.31487],
                        ['label' => 'Monterrey Sur', 'lat' => 25.65114, 'lng' => -100.27856],
                    ], JSON_UNESCAPED_SLASHES),
                    'notes' => 'Ruta en preparacion para el siguiente corte.',
                    'estado' => 'Preparacion',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
                'completed' => $this->upsertAndGetId('rutas', ['codigo' => 'GPQ-R-003'], [
                    'almacen_origen_id' => $warehouseIds['apodaca'],
                    'origen_almacen_id' => $warehouseIds['apodaca'],
                    'destino_almacen_id' => $warehouseIds['mty'],
                    'distancia_km' => 44.2,
                    'tiempo_estimado_min' => 105,
                    'estado_id' => (int) $routeStatuses['Completada'],
                    'route_code' => 'GPQ-R-003',
                    'vehicle_id' => $vehicleIds['boxTruck'],
                    'driver_id' => $driverIds['lucia'],
                    'warehouse_id' => $warehouseIds['apodaca'],
                    'scheduled_date' => $yesterday->toDateString(),
                    'start_time' => $yesterday->copy()->setTime(9, 10),
                    'end_time' => $yesterday->copy()->setTime(13, 0),
                    'total_packages' => 0,
                    'total_weight_kg' => 0,
                    'estimated_distance_km' => 44.2,
                    'actual_distance_km' => 46.1,
                    'estimated_time_minutes' => 105,
                    'actual_time_minutes' => 230,
                    'fuel_consumed_liters' => 17.8,
                    'status' => 'Completada',
                    'optimization_score' => 0,
                    'waypoints' => json_encode([
                        ['label' => 'Hub Apodaca', 'lat' => 25.77943, 'lng' => -100.18641],
                        ['label' => 'Guadalupe', 'lat' => 25.68802, 'lng' => -100.20831],
                        ['label' => 'San Nicolas', 'lat' => 25.74203, 'lng' => -100.30210],
                    ], JSON_UNESCAPED_SLASHES),
                    'notes' => 'Ruta cerrada para historico del tablero.',
                    'estado' => 'Completada',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            ];

            $shipmentIds = [
                'inRoute' => $this->upsertAndGetId('paquetes', ['codigo_tracking' => 'GPQ-250001'], [
                    'cliente_id' => $clientIds['carla'],
                    'tipo_id' => (int) $packageTypes['Carga general'],
                    'peso' => 340.0,
                    'peso_kg' => 340.0,
                    'volumen' => 0.28,
                    'volumen_m3' => 0.280,
                    'direccion_origen_id' => $addressIds['warehouseMty'],
                    'origen_direccion_id' => $addressIds['warehouseMty'],
                    'direccion_destino_id' => $addressIds['farmaciaMain'],
                    'destino_direccion_id' => $addressIds['farmaciaMain'],
                    'estado_id' => (int) $packageStatuses['En ruta'],
                    'estado' => 'En ruta',
                    'descripcion' => 'Reposicion de inventario de alto volumen para sucursal norte.',
                    'fecha_estimada_entrega' => $today->copy()->setTime(18, 30),
                    'tracking_code' => 'GPQ-250001',
                    'sender_id' => $clientIds['carla'],
                    'recipient_id' => $clientIds['farmacia'],
                    'recipient_address_id' => $customerAddressIds['farmaciaMain'],
                    'origin_warehouse_id' => $warehouseIds['mty'],
                    'weight_kg' => 340.0,
                    'quantity' => 12,
                    'volume_m3' => 0.2800,
                    'package_type' => 'Carga general',
                    'description' => 'Reposicion de inventario de alto volumen para sucursal norte.',
                    'declared_value' => 18500.0,
                    'recipient_address' => 'Av. Sendero 810',
                    'recipient_city' => 'San Nicolas',
                    'recipient_state' => 'Nuevo Leon',
                    'recipient_postal_code' => '66457',
                    'recipient_latitude' => 25.74203000,
                    'recipient_longitude' => -100.30210000,
                    'status' => 'En ruta',
                    'priority' => 'high',
                    'scheduled_date' => $today->toDateString(),
                    'assigned_at' => $today->copy()->setTime(8, 20),
                    'eta_at' => $today->copy()->setTime(18, 30),
                    'promised_date' => $today->toDateString(),
                    'pickup_time' => $today->copy()->setTime(8, 5),
                    'delivery_time' => null,
                    'attempts' => 0,
                    'notes' => 'Shipment demo GESTIONPAQ en ruta.',
                    'codigo_rastreo' => 'GPQ-250001',
                    'created_at' => $threeDaysAgo->copy()->setTime(10, 10),
                    'updated_at' => $now,
                ]),
                'deliveredToday' => $this->upsertAndGetId('paquetes', ['codigo_tracking' => 'GPQ-250002'], [
                    'cliente_id' => $clientIds['carla'],
                    'tipo_id' => (int) $packageTypes['Documentacion'],
                    'peso' => 280.0,
                    'peso_kg' => 280.0,
                    'volumen' => 0.22,
                    'volumen_m3' => 0.220,
                    'direccion_origen_id' => $addressIds['warehouseMty'],
                    'origen_direccion_id' => $addressIds['warehouseMty'],
                    'direccion_destino_id' => $addressIds['textilesMain'],
                    'destino_direccion_id' => $addressIds['textilesMain'],
                    'estado_id' => (int) $packageStatuses['Entregado'],
                    'estado' => 'Entregado',
                    'descripcion' => 'Expediente contractual con entrega confirmada.',
                    'fecha_estimada_entrega' => $today->copy()->setTime(15, 0),
                    'tracking_code' => 'GPQ-250002',
                    'sender_id' => $clientIds['carla'],
                    'recipient_id' => $clientIds['textiles'],
                    'recipient_address_id' => $customerAddressIds['textilesMain'],
                    'origin_warehouse_id' => $warehouseIds['mty'],
                    'weight_kg' => 280.0,
                    'quantity' => 9,
                    'volume_m3' => 0.2200,
                    'package_type' => 'Documentacion',
                    'description' => 'Expediente contractual con entrega confirmada.',
                    'declared_value' => 12600.0,
                    'recipient_address' => 'Av. Miguel de la Madrid 1501',
                    'recipient_city' => 'Guadalupe',
                    'recipient_state' => 'Nuevo Leon',
                    'recipient_postal_code' => '67130',
                    'recipient_latitude' => 25.68802000,
                    'recipient_longitude' => -100.20831000,
                    'status' => 'Entregado',
                    'priority' => 'express',
                    'scheduled_date' => $today->toDateString(),
                    'assigned_at' => $today->copy()->setTime(8, 10),
                    'eta_at' => $today->copy()->setTime(14, 30),
                    'promised_date' => $today->toDateString(),
                    'pickup_time' => $today->copy()->setTime(7, 55),
                    'delivery_time' => $today->copy()->setTime(14, 12),
                    'attempts' => 1,
                    'notes' => 'Shipment demo GESTIONPAQ entregado con evidencia.',
                    'codigo_rastreo' => 'GPQ-250002',
                    'created_at' => $twoDaysAgo->copy()->setTime(16, 40),
                    'updated_at' => $now,
                ]),
                'pendingAssigned' => $this->upsertAndGetId('paquetes', ['codigo_tracking' => 'GPQ-250003'], [
                    'cliente_id' => $clientIds['farmacia'],
                    'tipo_id' => (int) $packageTypes['Medicamento'],
                    'peso' => 140.0,
                    'peso_kg' => 140.0,
                    'volumen' => 0.16,
                    'volumen_m3' => 0.160,
                    'direccion_origen_id' => $addressIds['warehouseMty'],
                    'origen_direccion_id' => $addressIds['warehouseMty'],
                    'direccion_destino_id' => $addressIds['carlaHome'],
                    'destino_direccion_id' => $addressIds['carlaHome'],
                    'estado_id' => (int) $packageStatuses['Pendiente'],
                    'estado' => 'Pendiente',
                    'descripcion' => 'Pedido programado para la siguiente ventana de despacho.',
                    'fecha_estimada_entrega' => $tomorrow->copy()->setTime(16, 0),
                    'tracking_code' => 'GPQ-250003',
                    'sender_id' => $clientIds['farmacia'],
                    'recipient_id' => $clientIds['carla'],
                    'recipient_address_id' => $customerAddressIds['carlaHome'],
                    'origin_warehouse_id' => $warehouseIds['mty'],
                    'weight_kg' => 140.0,
                    'quantity' => 6,
                    'volume_m3' => 0.1600,
                    'package_type' => 'Medicamento',
                    'description' => 'Pedido programado para la siguiente ventana de despacho.',
                    'declared_value' => 9700.0,
                    'recipient_address' => 'Paseo del Acueducto 118',
                    'recipient_city' => 'Monterrey',
                    'recipient_state' => 'Nuevo Leon',
                    'recipient_postal_code' => '64349',
                    'recipient_latitude' => 25.73654000,
                    'recipient_longitude' => -100.36029000,
                    'status' => 'Pendiente',
                    'priority' => 'standard',
                    'scheduled_date' => $tomorrow->toDateString(),
                    'assigned_at' => $today->copy()->setTime(17, 10),
                    'eta_at' => $tomorrow->copy()->setTime(16, 0),
                    'promised_date' => $tomorrow->toDateString(),
                    'pickup_time' => null,
                    'delivery_time' => null,
                    'attempts' => 0,
                    'notes' => 'Shipment demo GESTIONPAQ en cola de salida.',
                    'codigo_rastreo' => 'GPQ-250003',
                    'created_at' => $yesterday->copy()->setTime(11, 15),
                    'updated_at' => $now,
                ]),
                'deliveredLate' => $this->upsertAndGetId('paquetes', ['codigo_tracking' => 'GPQ-250004'], [
                    'cliente_id' => $clientIds['textiles'],
                    'tipo_id' => (int) $packageTypes['Electronica'],
                    'peso' => 210.0,
                    'peso_kg' => 210.0,
                    'volumen' => 0.19,
                    'volumen_m3' => 0.190,
                    'direccion_origen_id' => $addressIds['warehouseApodaca'],
                    'origen_direccion_id' => $addressIds['warehouseApodaca'],
                    'direccion_destino_id' => $addressIds['farmaciaMain'],
                    'destino_direccion_id' => $addressIds['farmaciaMain'],
                    'estado_id' => (int) $packageStatuses['Entregado'],
                    'estado' => 'Entregado',
                    'descripcion' => 'Equipo de punto de venta con evidencia de entrega historica.',
                    'fecha_estimada_entrega' => $yesterday->copy()->setTime(12, 0),
                    'tracking_code' => 'GPQ-250004',
                    'sender_id' => $clientIds['textiles'],
                    'recipient_id' => $clientIds['farmacia'],
                    'recipient_address_id' => $customerAddressIds['farmaciaMain'],
                    'origin_warehouse_id' => $warehouseIds['apodaca'],
                    'weight_kg' => 210.0,
                    'quantity' => 5,
                    'volume_m3' => 0.1900,
                    'package_type' => 'Electronica',
                    'description' => 'Equipo de punto de venta con evidencia de entrega historica.',
                    'declared_value' => 25800.0,
                    'recipient_address' => 'Av. Sendero 810',
                    'recipient_city' => 'San Nicolas',
                    'recipient_state' => 'Nuevo Leon',
                    'recipient_postal_code' => '66457',
                    'recipient_latitude' => 25.74203000,
                    'recipient_longitude' => -100.30210000,
                    'status' => 'Entregado',
                    'priority' => 'high',
                    'scheduled_date' => $twoDaysAgo->toDateString(),
                    'assigned_at' => $twoDaysAgo->copy()->setTime(8, 10),
                    'eta_at' => $yesterday->copy()->setTime(12, 0),
                    'promised_date' => $twoDaysAgo->toDateString(),
                    'pickup_time' => $twoDaysAgo->copy()->setTime(9, 5),
                    'delivery_time' => $yesterday->copy()->setTime(13, 8),
                    'attempts' => 1,
                    'notes' => 'Shipment demo GESTIONPAQ entregado fuera de la promesa.',
                    'codigo_rastreo' => 'GPQ-250004',
                    'created_at' => $threeDaysAgo->copy()->setTime(9, 35),
                    'updated_at' => $now,
                ]),
                'registered' => $this->upsertAndGetId('paquetes', ['codigo_tracking' => 'GPQ-250005'], [
                    'cliente_id' => $clientIds['farmacia'],
                    'tipo_id' => (int) $packageTypes['Documentacion'],
                    'peso' => 30.0,
                    'peso_kg' => 30.0,
                    'volumen' => 0.04,
                    'volumen_m3' => 0.040,
                    'direccion_origen_id' => $addressIds['warehouseApodaca'],
                    'origen_direccion_id' => $addressIds['warehouseApodaca'],
                    'direccion_destino_id' => $addressIds['carlaOffice'],
                    'destino_direccion_id' => $addressIds['carlaOffice'],
                    'estado_id' => (int) $packageStatuses['Registrado'],
                    'estado' => 'Registrado',
                    'descripcion' => 'Sobre express registrado pendiente de asignacion.',
                    'fecha_estimada_entrega' => $tomorrow->copy()->setTime(13, 0),
                    'tracking_code' => 'GPQ-250005',
                    'sender_id' => $clientIds['farmacia'],
                    'recipient_id' => $clientIds['carla'],
                    'recipient_address_id' => $customerAddressIds['carlaOffice'],
                    'origin_warehouse_id' => $warehouseIds['apodaca'],
                    'weight_kg' => 30.0,
                    'quantity' => 2,
                    'volume_m3' => 0.0400,
                    'package_type' => 'Documentacion',
                    'description' => 'Sobre express registrado pendiente de asignacion.',
                    'declared_value' => 4200.0,
                    'recipient_address' => 'Av. Revolucion 4020',
                    'recipient_city' => 'Monterrey',
                    'recipient_state' => 'Nuevo Leon',
                    'recipient_postal_code' => '64860',
                    'recipient_latitude' => 25.65114000,
                    'recipient_longitude' => -100.27856000,
                    'status' => 'Registrado',
                    'priority' => 'standard',
                    'scheduled_date' => $tomorrow->toDateString(),
                    'assigned_at' => null,
                    'eta_at' => $tomorrow->copy()->setTime(13, 0),
                    'promised_date' => $tomorrow->toDateString(),
                    'pickup_time' => null,
                    'delivery_time' => null,
                    'attempts' => 0,
                    'notes' => 'Shipment demo GESTIONPAQ recien registrado.',
                    'codigo_rastreo' => 'GPQ-250005',
                    'created_at' => $today->copy()->setTime(7, 40),
                    'updated_at' => $now,
                ]),
            ];

            DB::table('asignaciones')->updateOrInsert(
                ['package_id' => $shipmentIds['inRoute']],
                [
                    'ruta_id' => $routeIds['active'],
                    'route_id' => $routeIds['active'],
                    'vehiculo_id' => $vehicleIds['sprinter'],
                    'vehicle_id' => $vehicleIds['sprinter'],
                    'conductor_id' => $driverIds['daniel'],
                    'warehouse_id' => $warehouseIds['mty'],
                    'sequence_order' => 1,
                    'status' => 'assigned',
                    'driver_id' => $driverIds['daniel'],
                    'dispatcher_user_id' => $userIds['dispatcher'],
                    'fecha_asignacion' => $today->copy()->setTime(8, 20),
                    'fecha_salida' => $today->copy()->setTime(8, 35),
                    'fecha_llegada_estimada' => $today->copy()->setTime(18, 30),
                    'estado' => 'en curso',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            DB::table('asignaciones')->updateOrInsert(
                ['package_id' => $shipmentIds['deliveredToday']],
                [
                    'ruta_id' => $routeIds['active'],
                    'route_id' => $routeIds['active'],
                    'vehiculo_id' => $vehicleIds['sprinter'],
                    'vehicle_id' => $vehicleIds['sprinter'],
                    'conductor_id' => $driverIds['daniel'],
                    'warehouse_id' => $warehouseIds['mty'],
                    'sequence_order' => 2,
                    'status' => 'delivered',
                    'driver_id' => $driverIds['daniel'],
                    'dispatcher_user_id' => $userIds['dispatcher'],
                    'fecha_asignacion' => $today->copy()->setTime(8, 10),
                    'fecha_salida' => $today->copy()->setTime(8, 30),
                    'fecha_llegada_estimada' => $today->copy()->setTime(14, 30),
                    'estado' => 'entregada',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            DB::table('asignaciones')->updateOrInsert(
                ['package_id' => $shipmentIds['pendingAssigned']],
                [
                    'ruta_id' => $routeIds['planned'],
                    'route_id' => $routeIds['planned'],
                    'vehiculo_id' => $vehicleIds['boxTruck'],
                    'vehicle_id' => $vehicleIds['boxTruck'],
                    'conductor_id' => $driverIds['lucia'],
                    'warehouse_id' => $warehouseIds['mty'],
                    'sequence_order' => 1,
                    'status' => 'assigned',
                    'driver_id' => $driverIds['lucia'],
                    'dispatcher_user_id' => $userIds['dispatcher'],
                    'fecha_asignacion' => $today->copy()->setTime(17, 10),
                    'fecha_salida' => null,
                    'fecha_llegada_estimada' => $tomorrow->copy()->setTime(16, 0),
                    'estado' => 'programada',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            DB::table('asignaciones')->updateOrInsert(
                ['package_id' => $shipmentIds['deliveredLate']],
                [
                    'ruta_id' => $routeIds['completed'],
                    'route_id' => $routeIds['completed'],
                    'vehiculo_id' => $vehicleIds['boxTruck'],
                    'vehicle_id' => $vehicleIds['boxTruck'],
                    'conductor_id' => $driverIds['lucia'],
                    'warehouse_id' => $warehouseIds['apodaca'],
                    'sequence_order' => 1,
                    'status' => 'delivered',
                    'driver_id' => $driverIds['lucia'],
                    'dispatcher_user_id' => $userIds['dispatcher'],
                    'fecha_asignacion' => $twoDaysAgo->copy()->setTime(8, 10),
                    'fecha_salida' => $twoDaysAgo->copy()->setTime(9, 5),
                    'fecha_llegada_estimada' => $twoDaysAgo->copy()->setTime(18, 0),
                    'estado' => 'entregada',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('mantenimiento')->updateOrInsert(
                ['vehicle_id' => $vehicleIds['sprinter'], 'scheduled_date' => $today->copy()->subDays(18)->toDateString(), 'type' => 'Preventivo'],
                [
                    'vehiculo_id' => $vehicleIds['sprinter'],
                    'tipo_id' => (int) $maintenanceTypes['Preventivo'],
                    'fecha' => $today->copy()->subDays(18)->toDateString(),
                    'costo' => 3200.0,
                    'descripcion' => 'Servicio preventivo completado para dataset demo GESTIONPAQ.',
                    'description' => 'Servicio preventivo completado para dataset demo GESTIONPAQ.',
                    'cost' => 3200.0,
                    'completion_date' => $today->copy()->subDays(17)->toDateString(),
                    'km_at_maintenance' => 17640.0,
                    'status' => 'completed',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            DB::table('mantenimiento')->updateOrInsert(
                ['vehicle_id' => $vehicleIds['maintenanceVan'], 'scheduled_date' => $today->toDateString(), 'type' => 'Correctivo'],
                [
                    'vehiculo_id' => $vehicleIds['maintenanceVan'],
                    'tipo_id' => (int) $maintenanceTypes['Correctivo'],
                    'fecha' => $today->toDateString(),
                    'costo' => 4850.0,
                    'descripcion' => 'Revision correctiva de frenos y suspension en curso.',
                    'description' => 'Revision correctiva de frenos y suspension en curso.',
                    'cost' => 4850.0,
                    'completion_date' => null,
                    'km_at_maintenance' => 31820.0,
                    'status' => 'in_progress',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $evidencePayloads = [
                [
                    'packageId' => $shipmentIds['deliveredToday'],
                    'assignmentId' => (int) DB::table('asignaciones')->where('package_id', $shipmentIds['deliveredToday'])->value('id'),
                    'driverId' => $driverIds['daniel'],
                    'routeId' => $routeIds['active'],
                    'timestamp' => $today->copy()->setTime(14, 12),
                    'recipientName' => 'Rafael Lozano',
                    'signatureText' => 'Rafael Lozano',
                    'notes' => 'Entrega confirmada sin incidencias en recepcion.',
                ],
                [
                    'packageId' => $shipmentIds['deliveredLate'],
                    'assignmentId' => (int) DB::table('asignaciones')->where('package_id', $shipmentIds['deliveredLate'])->value('id'),
                    'driverId' => $driverIds['lucia'],
                    'routeId' => $routeIds['completed'],
                    'timestamp' => $yesterday->copy()->setTime(13, 8),
                    'recipientName' => 'Mariana Paredes',
                    'signatureText' => 'Mariana Paredes',
                    'notes' => 'Entrega recibida despues de reprogramar acceso de muelle.',
                ],
            ];

            foreach ($evidencePayloads as $item) {
                DB::table('evidencias')->updateOrInsert(
                    ['package_id' => $item['packageId']],
                    [
                        'asignacion_id' => $item['assignmentId'],
                        'driver_id' => $item['driverId'],
                        'route_id' => $item['routeId'],
                        'delivery_timestamp' => $item['timestamp'],
                        'recipient_name' => $item['recipientName'],
                        'signature_path' => '/uploads/evidences/signatures/demo-signature-gpq.svg',
                        'photo_path' => '/uploads/evidences/photos/demo-delivery-gpq.svg',
                        'gps_latitude' => $item['packageId'] === $shipmentIds['deliveredToday'] ? 25.68802000 : 25.74203000,
                        'gps_longitude' => $item['packageId'] === $shipmentIds['deliveredToday'] ? -100.20831000 : -100.30210000,
                        'notes' => $item['notes'],
                        'status' => 'delivered',
                        'url_imagen' => '/uploads/evidences/photos/demo-delivery-gpq.svg',
                        'firma' => $item['signatureText'],
                        'fecha' => $item['timestamp'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            $demoShipmentIds = array_values($shipmentIds);
            DB::table('tracking')
                ->where(function ($query) use ($demoShipmentIds): void {
                    $query->whereIn('package_id', $demoShipmentIds)
                        ->orWhereIn('paquete_id', $demoShipmentIds);
                })
                ->delete();

            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['inRoute'],
                'Registro',
                'Envio registrado en mesa operativa.',
                'Cedis Monterrey',
                'Registrado',
                25.79456000,
                -100.31487000,
                $threeDaysAgo->copy()->setTime(10, 10)
            );
            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['inRoute'],
                'Asignacion',
                'Envio asignado a la ruta GPQ-R-001.',
                'Cedis Monterrey',
                'Pendiente',
                25.79456000,
                -100.31487000,
                $today->copy()->setTime(8, 20)
            );
            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['inRoute'],
                'Salida',
                'Unidad salida a reparto desde el cedis central.',
                'Monterrey',
                'En ruta',
                25.76831000,
                -100.30052000,
                $today->copy()->setTime(8, 35)
            );
            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['inRoute'],
                'Ultima milla',
                'Entrega en curso hacia la sucursal destino.',
                'San Nicolas',
                'En ruta',
                25.74203000,
                -100.30210000,
                $today->copy()->setTime(13, 40)
            );

            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['deliveredToday'],
                'Registro',
                'Envio registrado en mesa operativa.',
                'Cedis Monterrey',
                'Registrado',
                25.79456000,
                -100.31487000,
                $twoDaysAgo->copy()->setTime(16, 40)
            );
            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['deliveredToday'],
                'Asignacion',
                'Envio asignado a la ruta GPQ-R-001.',
                'Cedis Monterrey',
                'Pendiente',
                25.79456000,
                -100.31487000,
                $today->copy()->setTime(8, 10)
            );
            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['deliveredToday'],
                'Salida',
                'Unidad salida a reparto hacia Guadalupe.',
                'Monterrey',
                'En ruta',
                25.76831000,
                -100.30052000,
                $today->copy()->setTime(8, 30)
            );
            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['deliveredToday'],
                'Entrega',
                'Entrega confirmada con firma y fotografia.',
                'Guadalupe',
                'Entregado',
                25.68802000,
                -100.20831000,
                $today->copy()->setTime(14, 12)
            );

            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['pendingAssigned'],
                'Registro',
                'Pedido registrado para la siguiente ventana.',
                'Cedis Monterrey',
                'Registrado',
                25.79456000,
                -100.31487000,
                $yesterday->copy()->setTime(11, 15)
            );
            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['pendingAssigned'],
                'Planeacion',
                'Carga reservada en la ruta GPQ-R-002 para corte siguiente.',
                'Monterrey',
                'Pendiente',
                25.79456000,
                -100.31487000,
                $today->copy()->setTime(17, 10)
            );

            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['deliveredLate'],
                'Registro',
                'Envio registrado desde el hub de Apodaca.',
                'Hub Apodaca',
                'Registrado',
                25.77943000,
                -100.18641000,
                $threeDaysAgo->copy()->setTime(9, 35)
            );
            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['deliveredLate'],
                'Asignacion',
                'Envio asignado a la ruta GPQ-R-003.',
                'Hub Apodaca',
                'Pendiente',
                25.77943000,
                -100.18641000,
                $twoDaysAgo->copy()->setTime(8, 10)
            );
            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['deliveredLate'],
                'Salida',
                'Unidad salida a reparto con carga consolidada.',
                'Apodaca',
                'En ruta',
                25.77943000,
                -100.18641000,
                $twoDaysAgo->copy()->setTime(9, 5)
            );
            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['deliveredLate'],
                'Entrega',
                'Entrega completada despues de ajuste de acceso.',
                'San Nicolas',
                'Entregado',
                25.74203000,
                -100.30210000,
                $yesterday->copy()->setTime(13, 8)
            );

            LogisticsSupport::recordTrackingEvent(
                $shipmentIds['registered'],
                'Registro',
                'Sobre express registrado, pendiente de despacho.',
                'Hub Apodaca',
                'Registrado',
                25.77943000,
                -100.18641000,
                $today->copy()->setTime(7, 40)
            );

            foreach ($routeIds as $routeId) {
                LogisticsPlanner::syncRouteMetrics($routeId);
            }

            $this->seedExpandedOperations([
                'now' => $now,
                'today' => $today,
                'yesterday' => $yesterday,
                'twoDaysAgo' => $twoDaysAgo,
                'threeDaysAgo' => $threeDaysAgo,
                'tomorrow' => $tomorrow,
                'userIds' => $userIds,
                'packageStatuses' => $packageStatuses,
                'routeStatuses' => $routeStatuses,
                'vehicleStatuses' => $vehicleStatuses,
                'driverStatuses' => $driverStatuses,
                'vehicleTypes' => $vehicleTypes,
                'packageTypes' => $packageTypes,
                'maintenanceTypes' => $maintenanceTypes,
                'warehouseIds' => $warehouseIds,
                'vehicleIds' => $vehicleIds,
                'driverIds' => $driverIds,
                'clientIds' => $clientIds,
                'addressIds' => $addressIds,
                'customerAddressIds' => $customerAddressIds,
            ]);
        });
    }

    private function seedExpandedOperations(array $context): void
    {
        $now = $context['now'];
        $today = $context['today'];
        $yesterday = $context['yesterday'];
        $twoDaysAgo = $context['twoDaysAgo'];
        $threeDaysAgo = $context['threeDaysAgo'];
        $tomorrow = $context['tomorrow'];
        $dayAfterTomorrow = $tomorrow->copy()->addDay();
        $fourDaysAgo = $threeDaysAgo->copy()->subDay();
        $fiveDaysAgo = $threeDaysAgo->copy()->subDays(2);

        $userIds = $context['userIds'];
        $packageStatuses = $context['packageStatuses'];
        $routeStatuses = $context['routeStatuses'];
        $vehicleStatuses = $context['vehicleStatuses'];
        $driverStatuses = $context['driverStatuses'];
        $vehicleTypes = $context['vehicleTypes'];
        $packageTypes = $context['packageTypes'];
        $maintenanceTypes = $context['maintenanceTypes'];
        $warehouseIds = $context['warehouseIds'];
        $vehicleIds = $context['vehicleIds'];
        $driverIds = $context['driverIds'];
        $clientIds = $context['clientIds'];
        $addressIds = $context['addressIds'];
        $customerAddressIds = $context['customerAddressIds'];

        $locationCatalog = [
            'warehouseMty' => [
                'reference' => 'Cedis Monterrey',
                'address' => 'Av. Industria 2450',
                'city' => 'Monterrey',
                'state' => 'Nuevo Leon',
                'postal_code' => '66052',
                'latitude' => 25.79456000,
                'longitude' => -100.31487000,
                'direccion_id' => $addressIds['warehouseMty'],
            ],
            'warehouseApodaca' => [
                'reference' => 'Hub Apodaca',
                'address' => 'Carretera Miguel Aleman 980',
                'city' => 'Apodaca',
                'state' => 'Nuevo Leon',
                'postal_code' => '66603',
                'latitude' => 25.77943000,
                'longitude' => -100.18641000,
                'direccion_id' => $addressIds['warehouseApodaca'],
            ],
            'carlaHome' => [
                'reference' => 'Carla Mendoza - Casa',
                'address' => 'Paseo del Acueducto 118',
                'city' => 'Monterrey',
                'state' => 'Nuevo Leon',
                'postal_code' => '64349',
                'latitude' => 25.73654000,
                'longitude' => -100.36029000,
                'direccion_id' => $addressIds['carlaHome'],
            ],
            'carlaOffice' => [
                'reference' => 'Carla Mendoza - Oficina',
                'address' => 'Av. Revolucion 4020',
                'city' => 'Monterrey',
                'state' => 'Nuevo Leon',
                'postal_code' => '64860',
                'latitude' => 25.65114000,
                'longitude' => -100.27856000,
                'direccion_id' => $addressIds['carlaOffice'],
            ],
            'farmaciaMain' => [
                'reference' => 'Farmacia Centro - Recepcion',
                'address' => 'Av. Sendero 810',
                'city' => 'San Nicolas',
                'state' => 'Nuevo Leon',
                'postal_code' => '66457',
                'latitude' => 25.74203000,
                'longitude' => -100.30210000,
                'direccion_id' => $addressIds['farmaciaMain'],
            ],
            'textilesMain' => [
                'reference' => 'Textiles Oriente - Recibo',
                'address' => 'Av. Miguel de la Madrid 1501',
                'city' => 'Guadalupe',
                'state' => 'Nuevo Leon',
                'postal_code' => '67130',
                'latitude' => 25.68802000,
                'longitude' => -100.20831000,
                'direccion_id' => $addressIds['textilesMain'],
            ],
        ];

        $addressDefinitions = [
            'warehouseStc' => [
                'reference' => 'Crossdock Santa Catarina',
                'calle' => 'Av. Heberto Castillo',
                'numero' => '245',
                'colonia' => 'Parque Industrial Finsa',
                'ciudad' => 'Santa Catarina',
                'estado' => 'Nuevo Leon',
                'codigo_postal' => '66367',
                'numero_ext' => '245',
                'latitud' => 25.68392000,
                'longitud' => -100.45834000,
            ],
            'warehouseSal' => [
                'reference' => 'Punto Saltillo',
                'calle' => 'Blvd. Vito Alessio Robles',
                'numero' => '4400',
                'colonia' => 'Parque Industrial Saltillo-Ramos',
                'ciudad' => 'Saltillo',
                'estado' => 'Coahuila',
                'codigo_postal' => '25900',
                'numero_ext' => '4400',
                'latitud' => 25.43981000,
                'longitud' => -100.99532000,
            ],
            'autopartesMain' => [
                'reference' => 'Autopartes Sierra Norte - Embarques',
                'calle' => 'Av. Las Torres',
                'numero' => '880',
                'colonia' => 'Industrial del Poniente',
                'ciudad' => 'Santa Catarina',
                'estado' => 'Nuevo Leon',
                'codigo_postal' => '66350',
                'numero_ext' => '880',
                'latitud' => 25.68142000,
                'longitud' => -100.44124000,
            ],
            'clinicaMain' => [
                'reference' => 'Clinica San Pedro - Suministros',
                'calle' => 'Av. Vasconcelos',
                'numero' => '910',
                'colonia' => 'Del Valle',
                'ciudad' => 'San Pedro Garza Garcia',
                'estado' => 'Nuevo Leon',
                'codigo_postal' => '66220',
                'numero_ext' => '910',
                'latitud' => 25.65238000,
                'longitud' => -100.40611000,
            ],
            'refaccionesMain' => [
                'reference' => 'Refacciones Delta - Muelle',
                'calle' => 'Av. Benito Juarez',
                'numero' => '2401',
                'colonia' => 'Azteca',
                'ciudad' => 'Guadalupe',
                'estado' => 'Nuevo Leon',
                'codigo_postal' => '67150',
                'numero_ext' => '2401',
                'latitud' => 25.68615000,
                'longitud' => -100.25743000,
            ],
            'electrohogarMain' => [
                'reference' => 'ElectroHogar Valle - Recibo',
                'calle' => 'Av. Madero',
                'numero' => '1835',
                'colonia' => 'Centro',
                'ciudad' => 'Monterrey',
                'estado' => 'Nuevo Leon',
                'codigo_postal' => '64000',
                'numero_ext' => '1835',
                'latitud' => 25.67947000,
                'longitud' => -100.31386000,
            ],
            'hospitalMain' => [
                'reference' => 'Hospital Santa Elena - Almacen',
                'calle' => 'Av. Universidad',
                'numero' => '315',
                'colonia' => 'Anahuac',
                'ciudad' => 'San Nicolas',
                'estado' => 'Nuevo Leon',
                'codigo_postal' => '66450',
                'numero_ext' => '315',
                'latitud' => 25.74788000,
                'longitud' => -100.29877000,
            ],
            'agroMain' => [
                'reference' => 'Agroinsumos del Norte - Patio',
                'calle' => 'Carretera a Colombia',
                'numero' => '7800',
                'colonia' => 'Nueva Castilla',
                'ciudad' => 'Escobedo',
                'estado' => 'Nuevo Leon',
                'codigo_postal' => '66083',
                'numero_ext' => '7800',
                'latitud' => 25.80123000,
                'longitud' => -100.35541000,
            ],
            'bioplusMain' => [
                'reference' => 'Laboratorio BioPlus - Recepcion',
                'calle' => 'Av. Manuel Ordonez',
                'numero' => '3210',
                'colonia' => 'Industrial La Puerta',
                'ciudad' => 'Santa Catarina',
                'estado' => 'Nuevo Leon',
                'codigo_postal' => '66358',
                'numero_ext' => '3210',
                'latitud' => 25.69282000,
                'longitud' => -100.45931000,
            ],
            'libreriaMain' => [
                'reference' => 'Libreria Metropol - Cedis',
                'calle' => 'Av. Concordia',
                'numero' => '1402',
                'colonia' => 'Ebanos',
                'ciudad' => 'Apodaca',
                'estado' => 'Nuevo Leon',
                'codigo_postal' => '66612',
                'numero_ext' => '1402',
                'latitud' => 25.77926000,
                'longitud' => -100.22178000,
            ],
        ];

        foreach ($addressDefinitions as $key => $definition) {
            $addressIds[$key] = $this->upsertAddress($definition['reference'], [
                'calle' => $definition['calle'],
                'numero' => $definition['numero'],
                'colonia' => $definition['colonia'],
                'ciudad' => $definition['ciudad'],
                'estado' => $definition['estado'],
                'codigo_postal' => $definition['codigo_postal'],
                'numero_ext' => $definition['numero_ext'],
                'latitud' => $definition['latitud'],
                'longitud' => $definition['longitud'],
            ]);

            $locationCatalog[$key] = [
                'reference' => $definition['reference'],
                'address' => $definition['calle'].' '.$definition['numero'],
                'city' => $definition['ciudad'],
                'state' => $definition['estado'],
                'postal_code' => $definition['codigo_postal'],
                'latitude' => $definition['latitud'],
                'longitude' => $definition['longitud'],
                'direccion_id' => $addressIds[$key],
            ];
        }

        $warehouseIds['santaCatarina'] = $this->upsertAndGetId('almacenes', ['codigo' => 'ALM-GPQ-STC'], [
            'nombre' => 'Crossdock Santa Catarina',
            'code' => 'STC-CROSS',
            'direccion_id' => $addressIds['warehouseStc'],
            'address' => $locationCatalog['warehouseStc']['address'],
            'city' => $locationCatalog['warehouseStc']['city'],
            'state' => $locationCatalog['warehouseStc']['state'],
            'postal_code' => $locationCatalog['warehouseStc']['postal_code'],
            'latitude' => $locationCatalog['warehouseStc']['latitude'],
            'longitude' => $locationCatalog['warehouseStc']['longitude'],
            'capacity' => 620,
            'status' => 'active',
            'activo' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseIds['saltillo'] = $this->upsertAndGetId('almacenes', ['codigo' => 'ALM-GPQ-SAL'], [
            'nombre' => 'Punto Saltillo',
            'code' => 'SAL-PUNTO',
            'direccion_id' => $addressIds['warehouseSal'],
            'address' => $locationCatalog['warehouseSal']['address'],
            'city' => $locationCatalog['warehouseSal']['city'],
            'state' => $locationCatalog['warehouseSal']['state'],
            'postal_code' => $locationCatalog['warehouseSal']['postal_code'],
            'latitude' => $locationCatalog['warehouseSal']['latitude'],
            'longitude' => $locationCatalog['warehouseSal']['longitude'],
            'capacity' => 540,
            'status' => 'active',
            'activo' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $warehouseAddressKeys = [
            'mty' => 'warehouseMty',
            'apodaca' => 'warehouseApodaca',
            'santaCatarina' => 'warehouseStc',
            'saltillo' => 'warehouseSal',
        ];

        $contactDefinitions = [
            'autopartes' => [
                'email' => 'contacto@autopartessierranorte.mx',
                'person' => ['nombre' => 'Hector', 'apellido_paterno' => 'Molina', 'apellido_materno' => 'Trevino', 'telefono' => '8112203101', 'documento' => 'CLT-GPQ-004'],
            ],
            'clinica' => [
                'email' => 'logistica@clinicasanpedro.mx',
                'person' => ['nombre' => 'Adriana', 'apellido_paterno' => 'Garcia', 'apellido_materno' => 'Mendez', 'telefono' => '8112203102', 'documento' => 'CLT-GPQ-005'],
            ],
            'refacciones' => [
                'email' => 'embarques@refaccionesdelta.mx',
                'person' => ['nombre' => 'Luis', 'apellido_paterno' => 'Duarte', 'apellido_materno' => 'Cantu', 'telefono' => '8112203103', 'documento' => 'CLT-GPQ-006'],
            ],
            'electrohogar' => [
                'email' => 'abasto@electrohogarvalle.mx',
                'person' => ['nombre' => 'Paola', 'apellido_paterno' => 'Leal', 'apellido_materno' => 'Salinas', 'telefono' => '8112203104', 'documento' => 'CLT-GPQ-007'],
            ],
            'hospital' => [
                'email' => 'almacen@hospitalsantaelena.mx',
                'person' => ['nombre' => 'Emilio', 'apellido_paterno' => 'Rangel', 'apellido_materno' => 'Solis', 'telefono' => '8112203105', 'documento' => 'CLT-GPQ-008'],
            ],
            'agroinsumos' => [
                'email' => 'trafico@agroinsumosnorte.mx',
                'person' => ['nombre' => 'Martha', 'apellido_paterno' => 'Cruz', 'apellido_materno' => 'Vega', 'telefono' => '8112203106', 'documento' => 'CLT-GPQ-009'],
            ],
            'bioplus' => [
                'email' => 'operaciones@biopluslab.mx',
                'person' => ['nombre' => 'Jorge', 'apellido_paterno' => 'Villarreal', 'apellido_materno' => 'Santos', 'telefono' => '8112203107', 'documento' => 'CLT-GPQ-010'],
            ],
            'libreria' => [
                'email' => 'cedis@libreriametropol.mx',
                'person' => ['nombre' => 'Rocio', 'apellido_paterno' => 'Esquivel', 'apellido_materno' => 'Ayala', 'telefono' => '8112203108', 'documento' => 'CLT-GPQ-011'],
            ],
        ];

        $contactIds = [];

        foreach ($contactDefinitions as $key => $definition) {
            $contactIds[$key] = $this->upsertStandalonePerson($definition['email'], $definition['person']);
        }

        $clientDefinitions = [
            'autopartes' => ['code' => 'CLI-GPQ-004', 'contactKey' => 'autopartes', 'name' => 'Autopartes Sierra Norte', 'addressKey' => 'autopartesMain', 'serviceLevel' => 'corporativo', 'notes' => 'Cuenta B2B para autopartes de alta rotacion.'],
            'clinica' => ['code' => 'CLI-GPQ-005', 'contactKey' => 'clinica', 'name' => 'Clinica San Pedro', 'addressKey' => 'clinicaMain', 'serviceLevel' => 'premium', 'notes' => 'Cliente con ventanas estrictas y entregas urgentes.'],
            'refacciones' => ['code' => 'CLI-GPQ-006', 'contactKey' => 'refacciones', 'name' => 'Refacciones Delta', 'addressKey' => 'refaccionesMain', 'serviceLevel' => 'estandar', 'notes' => 'Operaciones recurrentes de refacciones industriales.'],
            'electrohogar' => ['code' => 'CLI-GPQ-007', 'contactKey' => 'electrohogar', 'name' => 'ElectroHogar Valle', 'addressKey' => 'electrohogarMain', 'serviceLevel' => 'corporativo', 'notes' => 'Retail de equipos electrodomesticos y accesorios.'],
            'hospital' => ['code' => 'CLI-GPQ-008', 'contactKey' => 'hospital', 'name' => 'Hospital Santa Elena', 'addressKey' => 'hospitalMain', 'serviceLevel' => 'premium', 'notes' => 'Cliente sensible a cadena de suministro clinica.'],
            'agroinsumos' => ['code' => 'CLI-GPQ-009', 'contactKey' => 'agroinsumos', 'name' => 'Agroinsumos del Norte', 'addressKey' => 'agroMain', 'serviceLevel' => 'estandar', 'notes' => 'Despachos programados hacia patio y campo.'],
            'bioplus' => ['code' => 'CLI-GPQ-010', 'contactKey' => 'bioplus', 'name' => 'Laboratorio BioPlus', 'addressKey' => 'bioplusMain', 'serviceLevel' => 'premium', 'notes' => 'Material de laboratorio con ventanas de entrega controladas.'],
            'libreria' => ['code' => 'CLI-GPQ-011', 'contactKey' => 'libreria', 'name' => 'Libreria Metropol', 'addressKey' => 'libreriaMain', 'serviceLevel' => 'corporativo', 'notes' => 'Resurtido escolar y papeleria por temporada.'],
        ];

        $clientNames = [
            'carla' => 'Carla Mendoza',
            'farmacia' => 'Farmacia Centro',
            'textiles' => 'Textiles Oriente',
        ];

        foreach ($clientDefinitions as $key => $definition) {
            $location = $locationCatalog[$definition['addressKey']];
            $contact = $contactDefinitions[$definition['contactKey']];

            $clientIds[$key] = $this->upsertClient($definition['code'], $contactIds[$definition['contactKey']], [
                'name' => $definition['name'],
                'email' => $contact['email'],
                'phone' => $contact['person']['telefono'],
                'identification' => $definition['code'],
                'type' => 'business',
                'status' => 'active',
                'default_address' => $location['address'].', '.$location['city'].', '.$location['state'],
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'notes' => $definition['notes'],
                'nivel_servicio' => $definition['serviceLevel'],
                'activo' => 1,
            ]);

            $customerAddressIds[$definition['addressKey']] = $this->upsertCustomerAddress($clientIds[$key], 'principal', [
                'direccion_id' => $location['direccion_id'],
                'address' => $location['address'],
                'city' => $location['city'],
                'state' => $location['state'],
                'postal_code' => $location['postal_code'],
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'is_default' => 1,
            ]);

            $clientNames[$key] = $definition['name'];
        }

        $vehicleDefinitions = [
            'urbanVan' => ['plate' => 'GPQ-552-D', 'warehouseKey' => 'santaCatarina', 'model' => 'Kangoo Maxi', 'brand' => 'Renault', 'year' => 2023, 'type' => 'Van', 'capacityKg' => 780, 'capacityPackages' => 34, 'currentFuel' => 49.0, 'fuelCapacity' => 58.0, 'fuelConsumptionKm' => 0.094, 'status' => 'Operativo', 'lastMaintenance' => $today->copy()->subDays(13)->setTime(13, 20), 'totalKm' => 14240.0, 'latitude' => 25.68392000, 'longitude' => -100.45834000, 'vin' => 'RNGPQKANGOO552D'],
            'intercityTruck' => ['plate' => 'GPQ-663-E', 'warehouseKey' => 'saltillo', 'model' => 'Hino 300', 'brand' => 'Hino', 'year' => 2022, 'type' => 'Camion ligero', 'capacityKg' => 3200, 'capacityPackages' => 120, 'currentFuel' => 102.0, 'fuelCapacity' => 130.0, 'fuelConsumptionKm' => 0.176, 'status' => 'Operativo', 'lastMaintenance' => $today->copy()->subDays(8)->setTime(10, 10), 'totalKm' => 28620.0, 'latitude' => 25.43981000, 'longitude' => -100.99532000, 'vin' => 'HNOGPQHINO663E'],
            'mtyReserve' => ['plate' => 'GPQ-774-F', 'warehouseKey' => 'mty', 'model' => 'Partner Rapid', 'brand' => 'Peugeot', 'year' => 2024, 'type' => 'Van', 'capacityKg' => 650, 'capacityPackages' => 28, 'currentFuel' => 41.0, 'fuelCapacity' => 50.0, 'fuelConsumptionKm' => 0.087, 'status' => 'Disponible', 'lastMaintenance' => $today->copy()->subDays(6)->setTime(15, 15), 'totalKm' => 9610.0, 'latitude' => 25.79211000, 'longitude' => -100.31201000, 'vin' => 'PEUGPQPARTNER774F'],
            'airportReserve' => ['plate' => 'GPQ-885-G', 'warehouseKey' => 'apodaca', 'model' => 'NV350', 'brand' => 'Nissan', 'year' => 2022, 'type' => 'Van', 'capacityKg' => 860, 'capacityPackages' => 36, 'currentFuel' => 46.0, 'fuelCapacity' => 65.0, 'fuelConsumptionKm' => 0.103, 'status' => 'Disponible', 'lastMaintenance' => $today->copy()->subDays(11)->setTime(9, 25), 'totalKm' => 17380.0, 'latitude' => 25.77926000, 'longitude' => -100.22178000, 'vin' => 'NISGPQNV350885G'],
            'saltilloTrailer' => ['plate' => 'GPQ-996-H', 'warehouseKey' => 'saltillo', 'model' => 'Cabstar', 'brand' => 'Nissan', 'year' => 2021, 'type' => 'Camion caja seca', 'capacityKg' => 4800, 'capacityPackages' => 160, 'currentFuel' => 82.0, 'fuelCapacity' => 125.0, 'fuelConsumptionKm' => 0.194, 'status' => 'Operativo', 'lastMaintenance' => $today->copy()->subDays(21)->setTime(16, 5), 'totalKm' => 40280.0, 'latitude' => 25.43942000, 'longitude' => -100.99477000, 'vin' => 'NISGPQCABSTAR996H'],
            'westSupport' => ['plate' => 'GPQ-407-J', 'warehouseKey' => 'santaCatarina', 'model' => 'Transit Courier', 'brand' => 'Ford', 'year' => 2023, 'type' => 'Van', 'capacityKg' => 700, 'capacityPackages' => 30, 'currentFuel' => 38.0, 'fuelCapacity' => 55.0, 'fuelConsumptionKm' => 0.091, 'status' => 'Disponible', 'lastMaintenance' => $today->copy()->subDays(17)->setTime(12, 0), 'totalKm' => 12890.0, 'latitude' => 25.68142000, 'longitude' => -100.44124000, 'vin' => 'FORDGPQCOURIER407J'],
            'apodacaRunner' => ['plate' => 'GPQ-624-L', 'warehouseKey' => 'apodaca', 'model' => 'Ram ProMaster City', 'brand' => 'Ram', 'year' => 2024, 'type' => 'Van', 'capacityKg' => 720, 'capacityPackages' => 32, 'currentFuel' => 44.0, 'fuelCapacity' => 60.0, 'fuelConsumptionKm' => 0.096, 'status' => 'Operativo', 'lastMaintenance' => $today->copy()->subDays(5)->setTime(8, 45), 'totalKm' => 8740.0, 'latitude' => 25.78136000, 'longitude' => -100.21487000, 'vin' => 'RAMGPQPROMASTER624L'],
        ];

        foreach ($vehicleDefinitions as $key => $definition) {
            $warehouseKey = $definition['warehouseKey'];
            $vehicleIds[$key] = $this->upsertAndGetId('vehiculos', ['placa' => $definition['plate']], [
                'warehouse_id' => $warehouseIds[$warehouseKey],
                'plate' => $definition['plate'],
                'model' => $definition['model'],
                'brand' => $definition['brand'],
                'year' => $definition['year'],
                'type' => $definition['type'],
                'capacity_kg' => $definition['capacityKg'],
                'capacity_packages' => $definition['capacityPackages'],
                'current_fuel' => $definition['currentFuel'],
                'fuel_capacity' => $definition['fuelCapacity'],
                'fuel_consumption_km' => $definition['fuelConsumptionKm'],
                'status' => $definition['status'],
                'last_maintenance' => $definition['lastMaintenance'],
                'total_km' => $definition['totalKm'],
                'latitude' => $definition['latitude'],
                'longitude' => $definition['longitude'],
                'vin' => $definition['vin'],
                'tipo_id' => (int) $vehicleTypes[$definition['type']],
                'capacidad' => (float) $definition['capacityKg'],
                'capacidad_kg' => $definition['capacityKg'],
                'estado_id' => (int) $vehicleStatuses[$definition['status']],
                'estado' => $definition['status'],
                'activo' => 1,
                'consumo_km' => round($definition['fuelConsumptionKm'], 2),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $driverPeople = [
            'marco' => ['email' => 'marco.tellez@gestionpaq.local', 'person' => ['nombre' => 'Marco', 'apellido_paterno' => 'Tellez', 'apellido_materno' => 'Campos', 'telefono' => '8112404101', 'documento' => 'DRV-GPQ-003']],
            'brenda' => ['email' => 'brenda.garza@gestionpaq.local', 'person' => ['nombre' => 'Brenda', 'apellido_paterno' => 'Garza', 'apellido_materno' => 'Nava', 'telefono' => '8112404102', 'documento' => 'DRV-GPQ-004']],
            'omar' => ['email' => 'omar.pena@gestionpaq.local', 'person' => ['nombre' => 'Omar', 'apellido_paterno' => 'Pena', 'apellido_materno' => 'Ortega', 'telefono' => '8112404103', 'documento' => 'DRV-GPQ-005']],
            'rosa' => ['email' => 'rosa.elizondo@gestionpaq.local', 'person' => ['nombre' => 'Rosa', 'apellido_paterno' => 'Elizondo', 'apellido_materno' => 'Mata', 'telefono' => '8112404104', 'documento' => 'DRV-GPQ-006']],
            'carlos' => ['email' => 'carlos.mireles@gestionpaq.local', 'person' => ['nombre' => 'Carlos', 'apellido_paterno' => 'Mireles', 'apellido_materno' => 'Peinado', 'telefono' => '8112404105', 'documento' => 'DRV-GPQ-007']],
            'ivan' => ['email' => 'ivan.castaneda@gestionpaq.local', 'person' => ['nombre' => 'Ivan', 'apellido_paterno' => 'Castaneda', 'apellido_materno' => 'Lara', 'telefono' => '8112404106', 'documento' => 'DRV-GPQ-008']],
            'elena' => ['email' => 'elena.leal@gestionpaq.local', 'person' => ['nombre' => 'Elena', 'apellido_paterno' => 'Leal', 'apellido_materno' => 'Suarez', 'telefono' => '8112404107', 'documento' => 'DRV-GPQ-009']],
        ];

        $driverPersonIds = [];

        foreach ($driverPeople as $key => $definition) {
            $driverPersonIds[$key] = $this->upsertStandalonePerson($definition['email'], $definition['person']);
        }

        $driverDefinitions = [
            'marco' => ['personKey' => 'marco', 'license' => 'LIC-GPQ-1003', 'expiry' => '2027-10-11', 'status' => 'En ruta', 'vehicleKey' => 'urbanVan', 'birthDate' => '1990-06-12', 'address' => 'Santa Catarina, Nuevo Leon'],
            'brenda' => ['personKey' => 'brenda', 'license' => 'LIC-GPQ-1004', 'expiry' => '2028-02-18', 'status' => 'En ruta', 'vehicleKey' => 'intercityTruck', 'birthDate' => '1987-04-23', 'address' => 'Saltillo, Coahuila'],
            'omar' => ['personKey' => 'omar', 'license' => 'LIC-GPQ-1005', 'expiry' => '2026-12-09', 'status' => 'Disponible', 'vehicleKey' => 'mtyReserve', 'birthDate' => '1994-11-08', 'address' => 'Monterrey, Nuevo Leon'],
            'rosa' => ['personKey' => 'rosa', 'license' => 'LIC-GPQ-1006', 'expiry' => '2027-07-21', 'status' => 'Activo', 'vehicleKey' => 'airportReserve', 'birthDate' => '1991-01-17', 'address' => 'Apodaca, Nuevo Leon'],
            'carlos' => ['personKey' => 'carlos', 'license' => 'LIC-GPQ-1007', 'expiry' => '2027-05-30', 'status' => 'Disponible', 'vehicleKey' => 'saltilloTrailer', 'birthDate' => '1986-09-02', 'address' => 'Ramos Arizpe, Coahuila'],
            'ivan' => ['personKey' => 'ivan', 'license' => 'LIC-GPQ-1008', 'expiry' => '2028-01-15', 'status' => 'En ruta', 'vehicleKey' => 'apodacaRunner', 'birthDate' => '1993-12-27', 'address' => 'Apodaca, Nuevo Leon'],
            'elena' => ['personKey' => 'elena', 'license' => 'LIC-GPQ-1009', 'expiry' => '2026-09-14', 'status' => 'Fuera de turno', 'vehicleKey' => 'westSupport', 'birthDate' => '1989-08-09', 'address' => 'Santa Catarina, Nuevo Leon'],
        ];

        foreach ($driverDefinitions as $key => $definition) {
            $person = $driverPeople[$definition['personKey']];
            $driverIds[$key] = $this->upsertDriver($driverPersonIds[$definition['personKey']], [
                'numero_licencia' => $definition['license'],
                'licencia_vence' => $definition['expiry'],
                'activo' => 1,
                'estado_id' => (int) $driverStatuses[$definition['status']],
                'name' => $person['person']['nombre'].' '.$person['person']['apellido_paterno'],
                'email' => $person['email'],
                'phone' => $person['person']['telefono'],
                'license_number' => $definition['license'],
                'license_expiry' => $definition['expiry'],
                'identification' => $person['person']['documento'],
                'date_of_birth' => $definition['birthDate'],
                'address' => $definition['address'],
                'status' => $definition['status'],
                'current_vehicle_id' => $vehicleIds[$definition['vehicleKey']],
                'latitude' => $vehicleDefinitions[$definition['vehicleKey']]['latitude'],
                'longitude' => $vehicleDefinitions[$definition['vehicleKey']]['longitude'],
                'last_seen_at' => $now->copy()->subMinutes(rand(4, 32)),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $upsertShift = function (string $driverKey, $date, string $startTime, string $endTime, int $totalDeliveries, int $successfulDeliveries, int $failedDeliveries, float $distanceKm, string $status, string $state, ?string $closedAt = null) use (&$driverIds, $now): void {
            DB::table('turnos_conductor')->updateOrInsert(
                ['driver_id' => $driverIds[$driverKey], 'shift_date' => $date->toDateString()],
                [
                    'conductor_id' => $driverIds[$driverKey],
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'total_deliveries' => $totalDeliveries,
                    'successful_deliveries' => $successfulDeliveries,
                    'failed_deliveries' => $failedDeliveries,
                    'distance_km' => $distanceKm,
                    'status' => $status,
                    'inicio_turno' => $date->copy()->setTime((int) substr($startTime, 0, 2), (int) substr($startTime, 3, 2)),
                    'fin_turno' => $closedAt ? $date->copy()->setTime((int) substr($closedAt, 0, 2), (int) substr($closedAt, 3, 2)) : null,
                    'estado' => $state,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        };

        $upsertShift('marco', $today, '07:30:00', '18:30:00', 8, 5, 0, 64.8, 'in_progress', 'activo');
        $upsertShift('marco', $tomorrow, '07:30:00', '18:30:00', 0, 0, 0, 0.0, 'scheduled', 'programado');
        $upsertShift('brenda', $today, '06:45:00', '17:30:00', 6, 3, 0, 128.4, 'in_progress', 'activo');
        $upsertShift('omar', $today, '08:00:00', '17:00:00', 1, 1, 0, 14.2, 'scheduled', 'activo');
        $upsertShift('omar', $tomorrow, '08:00:00', '17:00:00', 0, 0, 0, 0.0, 'scheduled', 'programado');
        $upsertShift('rosa', $today, '07:00:00', '16:00:00', 2, 2, 0, 22.6, 'scheduled', 'activo');
        $upsertShift('rosa', $tomorrow, '07:00:00', '16:00:00', 0, 0, 0, 0.0, 'scheduled', 'programado');
        $upsertShift('carlos', $yesterday, '07:10:00', '16:20:00', 7, 7, 0, 118.0, 'completed', 'cerrado', '16:25');
        $upsertShift('carlos', $today, '08:30:00', '17:30:00', 0, 0, 0, 0.0, 'scheduled', 'activo');
        $upsertShift('ivan', $today, '07:20:00', '18:00:00', 7, 4, 0, 58.1, 'in_progress', 'activo');
        $upsertShift('elena', $yesterday, '08:15:00', '16:10:00', 5, 5, 0, 39.3, 'completed', 'cerrado', '16:15');
        $upsertShift('elena', $today, '07:00:00', '15:00:00', 0, 0, 0, 0.0, 'scheduled', 'cerrado', '15:05');

        $routeDefinitions = [
            'urbanWest' => ['code' => 'GPQ-R-010', 'warehouseKey' => 'santaCatarina', 'destinationWarehouseKey' => 'mty', 'distanceKm' => 26.4, 'timeMinutes' => 70, 'status' => 'En ejecucion', 'vehicleKey' => 'urbanVan', 'driverKey' => 'marco', 'scheduledDate' => $today, 'startTime' => $today->copy()->setTime(9, 10), 'endTime' => null, 'actualDistanceKm' => 15.7, 'actualTimeMinutes' => 52, 'fuelConsumedLiters' => 5.8, 'notes' => 'Ruta urbana oeste con entregas empresariales.'],
            'intercityNorth' => ['code' => 'GPQ-R-011', 'warehouseKey' => 'saltillo', 'destinationWarehouseKey' => 'mty', 'distanceKm' => 88.7, 'timeMinutes' => 125, 'status' => 'En ejecucion', 'vehicleKey' => 'intercityTruck', 'driverKey' => 'brenda', 'scheduledDate' => $today, 'startTime' => $today->copy()->setTime(7, 50), 'endTime' => null, 'actualDistanceKm' => 63.4, 'actualTimeMinutes' => 104, 'fuelConsumedLiters' => 16.2, 'notes' => 'Ruta interurbana Saltillo-Monterrey con carga consolidada.'],
            'futureCentral' => ['code' => 'GPQ-R-012', 'warehouseKey' => 'mty', 'destinationWarehouseKey' => 'mty', 'distanceKm' => 31.5, 'timeMinutes' => 84, 'status' => 'Preparacion', 'vehicleKey' => 'mtyReserve', 'driverKey' => null, 'scheduledDate' => $tomorrow, 'startTime' => null, 'endTime' => null, 'actualDistanceKm' => 0.0, 'actualTimeMinutes' => 0, 'fuelConsumedLiters' => 0.0, 'notes' => 'Ruta futura del centro metropolitano pendiente de conductor.'],
            'futureAirport' => ['code' => 'GPQ-R-013', 'warehouseKey' => 'apodaca', 'destinationWarehouseKey' => 'apodaca', 'distanceKm' => 24.8, 'timeMinutes' => 66, 'status' => 'Preparacion', 'vehicleKey' => 'airportReserve', 'driverKey' => null, 'scheduledDate' => $tomorrow, 'startTime' => null, 'endTime' => null, 'actualDistanceKm' => 0.0, 'actualTimeMinutes' => 0, 'fuelConsumedLiters' => 0.0, 'notes' => 'Ruta futura del corredor aeropuerto pendiente de conductor.'],
            'completedWest' => ['code' => 'GPQ-R-014', 'warehouseKey' => 'santaCatarina', 'destinationWarehouseKey' => 'mty', 'distanceKm' => 34.2, 'timeMinutes' => 92, 'status' => 'Completada', 'vehicleKey' => 'westSupport', 'driverKey' => 'elena', 'scheduledDate' => $yesterday, 'startTime' => $yesterday->copy()->setTime(8, 25), 'endTime' => $yesterday->copy()->setTime(13, 35), 'actualDistanceKm' => 36.1, 'actualTimeMinutes' => 182, 'fuelConsumedLiters' => 8.4, 'notes' => 'Historico cerrado del corredor poniente.'],
            'completedSaltillo' => ['code' => 'GPQ-R-015', 'warehouseKey' => 'saltillo', 'destinationWarehouseKey' => 'mty', 'distanceKm' => 92.4, 'timeMinutes' => 135, 'status' => 'Completada', 'vehicleKey' => 'saltilloTrailer', 'driverKey' => 'carlos', 'scheduledDate' => $twoDaysAgo, 'startTime' => $twoDaysAgo->copy()->setTime(6, 50), 'endTime' => $twoDaysAgo->copy()->setTime(14, 30), 'actualDistanceKm' => 95.9, 'actualTimeMinutes' => 276, 'fuelConsumedLiters' => 19.1, 'notes' => 'Historico cerrado para la plaza Saltillo.'],
            'activeAirport' => ['code' => 'GPQ-R-016', 'warehouseKey' => 'apodaca', 'destinationWarehouseKey' => 'mty', 'distanceKm' => 28.6, 'timeMinutes' => 72, 'status' => 'En ejecucion', 'vehicleKey' => 'apodacaRunner', 'driverKey' => 'ivan', 'scheduledDate' => $today, 'startTime' => $today->copy()->setTime(8, 40), 'endTime' => null, 'actualDistanceKm' => 17.8, 'actualTimeMinutes' => 59, 'fuelConsumedLiters' => 6.2, 'notes' => 'Ruta activa del corredor aeropuerto y centro.'],
            'completedCentral' => ['code' => 'GPQ-R-017', 'warehouseKey' => 'mty', 'destinationWarehouseKey' => 'apodaca', 'distanceKm' => 33.7, 'timeMinutes' => 88, 'status' => 'Completada', 'vehicleKey' => 'boxTruck', 'driverKey' => 'lucia', 'scheduledDate' => $threeDaysAgo, 'startTime' => $threeDaysAgo->copy()->setTime(8, 5), 'endTime' => $threeDaysAgo->copy()->setTime(12, 55), 'actualDistanceKm' => 35.0, 'actualTimeMinutes' => 174, 'fuelConsumedLiters' => 11.6, 'notes' => 'Historico completado de entregas centro-oriente.'],
        ];

        $routeIds = [];
        $routeMeta = [];

        foreach ($routeDefinitions as $key => $definition) {
            $routeIds[$key] = $this->upsertAndGetId('rutas', ['codigo' => $definition['code']], [
                'almacen_origen_id' => $warehouseIds[$definition['warehouseKey']],
                'origen_almacen_id' => $warehouseIds[$definition['warehouseKey']],
                'destino_almacen_id' => $warehouseIds[$definition['destinationWarehouseKey']],
                'distancia_km' => $definition['distanceKm'],
                'tiempo_estimado_min' => $definition['timeMinutes'],
                'estado_id' => (int) $routeStatuses[$definition['status']],
                'route_code' => $definition['code'],
                'vehicle_id' => $vehicleIds[$definition['vehicleKey']],
                'driver_id' => $definition['driverKey'] ? $driverIds[$definition['driverKey']] : null,
                'warehouse_id' => $warehouseIds[$definition['warehouseKey']],
                'scheduled_date' => $definition['scheduledDate']->toDateString(),
                'start_time' => $definition['startTime'],
                'end_time' => $definition['endTime'],
                'total_packages' => 0,
                'total_weight_kg' => 0,
                'estimated_distance_km' => $definition['distanceKm'],
                'actual_distance_km' => $definition['actualDistanceKm'],
                'estimated_time_minutes' => $definition['timeMinutes'],
                'actual_time_minutes' => $definition['actualTimeMinutes'],
                'fuel_consumed_liters' => $definition['fuelConsumedLiters'],
                'status' => $definition['status'],
                'optimization_score' => 0,
                'waypoints' => json_encode([
                    ['label' => $locationCatalog[$warehouseAddressKeys[$definition['warehouseKey']]]['reference'], 'lat' => $locationCatalog[$warehouseAddressKeys[$definition['warehouseKey']]]['latitude'], 'lng' => $locationCatalog[$warehouseAddressKeys[$definition['warehouseKey']]]['longitude']],
                    ['label' => $definition['destinationWarehouseKey'] === $definition['warehouseKey'] ? 'Circuito metropolitano' : $locationCatalog[$warehouseAddressKeys[$definition['destinationWarehouseKey']]]['reference'], 'lat' => $locationCatalog[$warehouseAddressKeys[$definition['destinationWarehouseKey']]]['latitude'], 'lng' => $locationCatalog[$warehouseAddressKeys[$definition['destinationWarehouseKey']]]['longitude']],
                ], JSON_UNESCAPED_SLASHES),
                'notes' => $definition['notes'],
                'estado' => $definition['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $routeMeta[$key] = [
                'id' => $routeIds[$key],
                'vehicleId' => $vehicleIds[$definition['vehicleKey']],
                'driverId' => $definition['driverKey'] ? $driverIds[$definition['driverKey']] : null,
                'warehouseId' => $warehouseIds[$definition['warehouseKey']],
                'warehouseKey' => $definition['warehouseKey'],
                'status' => $definition['status'],
                'scheduledDate' => $definition['scheduledDate'],
            ];
        }

        $shipmentBlueprints = [
            ['tracking' => 'GPQ-260010', 'routeKey' => 'urbanWest', 'warehouseKey' => 'santaCatarina', 'senderKey' => 'autopartes', 'recipientKey' => 'clinica', 'addressKey' => 'clinicaMain', 'packageType' => 'Electronica', 'weight' => 180.0, 'quantity' => 4, 'volume' => 0.1400, 'status' => 'En ruta', 'priority' => 'high', 'declaredValue' => 19500.0, 'description' => 'Terminales de cobro y lectores para modulo clinico.'],
            ['tracking' => 'GPQ-260011', 'routeKey' => 'urbanWest', 'warehouseKey' => 'santaCatarina', 'senderKey' => 'electrohogar', 'recipientKey' => 'hospital', 'addressKey' => 'hospitalMain', 'packageType' => 'Carga general', 'weight' => 220.0, 'quantity' => 7, 'volume' => 0.1900, 'status' => 'En ruta', 'priority' => 'high', 'declaredValue' => 22800.0, 'description' => 'Reposicion de equipos pequeños y refacciones de soporte.'],
            ['tracking' => 'GPQ-260012', 'routeKey' => 'urbanWest', 'warehouseKey' => 'santaCatarina', 'senderKey' => 'libreria', 'recipientKey' => 'carla', 'addressKey' => 'carlaOffice', 'packageType' => 'Documentacion', 'weight' => 45.0, 'quantity' => 5, 'volume' => 0.0500, 'status' => 'Entregado', 'priority' => 'express', 'declaredValue' => 3900.0, 'description' => 'Entrega de contratos y guias escolares urgentes.'],
            ['tracking' => 'GPQ-260013', 'routeKey' => 'urbanWest', 'warehouseKey' => 'santaCatarina', 'senderKey' => 'bioplus', 'recipientKey' => 'farmacia', 'addressKey' => 'farmaciaMain', 'packageType' => 'Medicamento', 'weight' => 95.0, 'quantity' => 3, 'volume' => 0.0700, 'status' => 'Entregado', 'priority' => 'high', 'declaredValue' => 12800.0, 'description' => 'Lotes de laboratorio sensibles a temperatura controlada.'],
            ['tracking' => 'GPQ-260014', 'routeKey' => 'intercityNorth', 'warehouseKey' => 'saltillo', 'senderKey' => 'agroinsumos', 'recipientKey' => 'autopartes', 'addressKey' => 'autopartesMain', 'packageType' => 'Carga general', 'weight' => 640.0, 'quantity' => 14, 'volume' => 0.4200, 'status' => 'En ruta', 'priority' => 'standard', 'declaredValue' => 34200.0, 'description' => 'Abasto interurbano con consolidado mixto de patio.'],
            ['tracking' => 'GPQ-260015', 'routeKey' => 'intercityNorth', 'warehouseKey' => 'saltillo', 'senderKey' => 'clinica', 'recipientKey' => 'carla', 'addressKey' => 'carlaHome', 'packageType' => 'Documentacion', 'weight' => 36.0, 'quantity' => 2, 'volume' => 0.0300, 'status' => 'En ruta', 'priority' => 'express', 'declaredValue' => 5200.0, 'description' => 'Expediente medico y dispositivos de validacion remota.'],
            ['tracking' => 'GPQ-260016', 'routeKey' => 'intercityNorth', 'warehouseKey' => 'saltillo', 'senderKey' => 'refacciones', 'recipientKey' => 'textiles', 'addressKey' => 'textilesMain', 'packageType' => 'Electronica', 'weight' => 270.0, 'quantity' => 6, 'volume' => 0.1800, 'status' => 'En ruta', 'priority' => 'high', 'declaredValue' => 26400.0, 'description' => 'Tarjetas y modulos de control para maquinaria de planta.'],
            ['tracking' => 'GPQ-260017', 'routeKey' => 'intercityNorth', 'warehouseKey' => 'saltillo', 'senderKey' => 'hospital', 'recipientKey' => 'farmacia', 'addressKey' => 'farmaciaMain', 'packageType' => 'Medicamento', 'weight' => 120.0, 'quantity' => 4, 'volume' => 0.0900, 'status' => 'Entregado', 'priority' => 'high', 'declaredValue' => 18600.0, 'description' => 'Entrega parcial de medicamentos y material curativo.'],
            ['tracking' => 'GPQ-260018', 'routeKey' => 'futureCentral', 'warehouseKey' => 'mty', 'senderKey' => 'electrohogar', 'recipientKey' => 'carla', 'addressKey' => 'carlaHome', 'packageType' => 'Carga general', 'weight' => 130.0, 'quantity' => 4, 'volume' => 0.1100, 'status' => 'Pendiente', 'priority' => 'standard', 'declaredValue' => 9800.0, 'description' => 'Consolidado de accesorios de hogar para entrega residencial.'],
            ['tracking' => 'GPQ-260019', 'routeKey' => 'futureCentral', 'warehouseKey' => 'mty', 'senderKey' => 'libreria', 'recipientKey' => 'clinica', 'addressKey' => 'clinicaMain', 'packageType' => 'Documentacion', 'weight' => 28.0, 'quantity' => 3, 'volume' => 0.0300, 'status' => 'Registrado', 'priority' => 'standard', 'declaredValue' => 2600.0, 'description' => 'Paquete documental para compras y archivo medico.'],
            ['tracking' => 'GPQ-260020', 'routeKey' => 'futureCentral', 'warehouseKey' => 'mty', 'senderKey' => 'bioplus', 'recipientKey' => 'hospital', 'addressKey' => 'hospitalMain', 'packageType' => 'Medicamento', 'weight' => 88.0, 'quantity' => 3, 'volume' => 0.0600, 'status' => 'Pendiente', 'priority' => 'high', 'declaredValue' => 14200.0, 'description' => 'Reactivos programados para ventana de laboratorio del dia siguiente.'],
            ['tracking' => 'GPQ-260021', 'routeKey' => 'futureCentral', 'warehouseKey' => 'mty', 'senderKey' => 'autopartes', 'recipientKey' => 'refacciones', 'addressKey' => 'refaccionesMain', 'packageType' => 'Carga general', 'weight' => 210.0, 'quantity' => 6, 'volume' => 0.1600, 'status' => 'Pendiente', 'priority' => 'standard', 'declaredValue' => 15600.0, 'description' => 'Pedido cruzado de inventario para mostrador industrial.'],
            ['tracking' => 'GPQ-260022', 'routeKey' => 'futureAirport', 'warehouseKey' => 'apodaca', 'senderKey' => 'farmacia', 'recipientKey' => 'electrohogar', 'addressKey' => 'electrohogarMain', 'packageType' => 'Medicamento', 'weight' => 74.0, 'quantity' => 2, 'volume' => 0.0500, 'status' => 'Pendiente', 'priority' => 'high', 'declaredValue' => 11800.0, 'description' => 'Entrega de botiquin corporativo y consumibles controlados.'],
            ['tracking' => 'GPQ-260023', 'routeKey' => 'futureAirport', 'warehouseKey' => 'apodaca', 'senderKey' => 'carla', 'recipientKey' => 'libreria', 'addressKey' => 'libreriaMain', 'packageType' => 'Documentacion', 'weight' => 18.0, 'quantity' => 1, 'volume' => 0.0200, 'status' => 'Registrado', 'priority' => 'standard', 'declaredValue' => 1800.0, 'description' => 'Sobre con ordenes de compra y anexos fiscales.'],
            ['tracking' => 'GPQ-260024', 'routeKey' => 'futureAirport', 'warehouseKey' => 'apodaca', 'senderKey' => 'refacciones', 'recipientKey' => 'bioplus', 'addressKey' => 'bioplusMain', 'packageType' => 'Electronica', 'weight' => 156.0, 'quantity' => 4, 'volume' => 0.1200, 'status' => 'Pendiente', 'priority' => 'high', 'declaredValue' => 13700.0, 'description' => 'Sensores y modulos de prueba para equipo de laboratorio.'],
            ['tracking' => 'GPQ-260025', 'routeKey' => 'futureAirport', 'warehouseKey' => 'apodaca', 'senderKey' => 'agroinsumos', 'recipientKey' => 'textiles', 'addressKey' => 'textilesMain', 'packageType' => 'Carga general', 'weight' => 240.0, 'quantity' => 8, 'volume' => 0.1800, 'status' => 'Registrado', 'priority' => 'standard', 'declaredValue' => 11900.0, 'description' => 'Resurtido cruzado para inventario de temporada.'],
            ['tracking' => 'GPQ-260026', 'routeKey' => 'completedWest', 'warehouseKey' => 'santaCatarina', 'senderKey' => 'clinica', 'recipientKey' => 'hospital', 'addressKey' => 'hospitalMain', 'packageType' => 'Medicamento', 'weight' => 92.0, 'quantity' => 3, 'volume' => 0.0700, 'status' => 'Entregado', 'priority' => 'high', 'declaredValue' => 15400.0, 'description' => 'Entrega cerrada de suministros clinicos de alta prioridad.'],
            ['tracking' => 'GPQ-260027', 'routeKey' => 'completedWest', 'warehouseKey' => 'santaCatarina', 'senderKey' => 'electrohogar', 'recipientKey' => 'autopartes', 'addressKey' => 'autopartesMain', 'packageType' => 'Electronica', 'weight' => 135.0, 'quantity' => 4, 'volume' => 0.0900, 'status' => 'Entregado', 'priority' => 'standard', 'declaredValue' => 9800.0, 'description' => 'Accesorios electricos entregados a planta de autopartes.'],
            ['tracking' => 'GPQ-260028', 'routeKey' => 'completedWest', 'warehouseKey' => 'santaCatarina', 'senderKey' => 'libreria', 'recipientKey' => 'carla', 'addressKey' => 'carlaOffice', 'packageType' => 'Documentacion', 'weight' => 22.0, 'quantity' => 3, 'volume' => 0.0200, 'status' => 'Entregado', 'priority' => 'express', 'declaredValue' => 2500.0, 'description' => 'Entrega documental para corte administrativo semanal.'],
            ['tracking' => 'GPQ-260029', 'routeKey' => 'completedWest', 'warehouseKey' => 'santaCatarina', 'senderKey' => 'bioplus', 'recipientKey' => 'textiles', 'addressKey' => 'textilesMain', 'packageType' => 'Electronica', 'weight' => 164.0, 'quantity' => 5, 'volume' => 0.1100, 'status' => 'Entregado', 'priority' => 'high', 'declaredValue' => 16300.0, 'description' => 'Lectores y sensores entregados a planta textil.'],
            ['tracking' => 'GPQ-260030', 'routeKey' => 'completedSaltillo', 'warehouseKey' => 'saltillo', 'senderKey' => 'agroinsumos', 'recipientKey' => 'farmacia', 'addressKey' => 'farmaciaMain', 'packageType' => 'Carga general', 'weight' => 310.0, 'quantity' => 9, 'volume' => 0.2300, 'status' => 'Entregado', 'priority' => 'standard', 'declaredValue' => 17300.0, 'description' => 'Pedido regional consolidado para sucursal norte.'],
            ['tracking' => 'GPQ-260031', 'routeKey' => 'completedSaltillo', 'warehouseKey' => 'saltillo', 'senderKey' => 'hospital', 'recipientKey' => 'clinica', 'addressKey' => 'clinicaMain', 'packageType' => 'Medicamento', 'weight' => 84.0, 'quantity' => 3, 'volume' => 0.0600, 'status' => 'Entregado', 'priority' => 'high', 'declaredValue' => 14900.0, 'description' => 'Entrega completada de kits y abastecimiento clinico.'],
            ['tracking' => 'GPQ-260032', 'routeKey' => 'completedSaltillo', 'warehouseKey' => 'saltillo', 'senderKey' => 'refacciones', 'recipientKey' => 'carla', 'addressKey' => 'carlaHome', 'packageType' => 'Carga general', 'weight' => 112.0, 'quantity' => 4, 'volume' => 0.0800, 'status' => 'Entregado', 'priority' => 'standard', 'declaredValue' => 7100.0, 'description' => 'Refacciones ligeras entregadas en domicilio corporativo.'],
            ['tracking' => 'GPQ-260033', 'routeKey' => 'completedSaltillo', 'warehouseKey' => 'saltillo', 'senderKey' => 'autopartes', 'recipientKey' => 'electrohogar', 'addressKey' => 'electrohogarMain', 'packageType' => 'Electronica', 'weight' => 188.0, 'quantity' => 6, 'volume' => 0.1200, 'status' => 'Entregado', 'priority' => 'high', 'declaredValue' => 20100.0, 'description' => 'Componentes de control entregados a piso de ventas.'],
            ['tracking' => 'GPQ-260034', 'routeKey' => 'activeAirport', 'warehouseKey' => 'apodaca', 'senderKey' => 'farmacia', 'recipientKey' => 'libreria', 'addressKey' => 'libreriaMain', 'packageType' => 'Medicamento', 'weight' => 66.0, 'quantity' => 2, 'volume' => 0.0500, 'status' => 'En ruta', 'priority' => 'high', 'declaredValue' => 8600.0, 'description' => 'Botiquines corporativos en reparto hacia cedis editorial.'],
            ['tracking' => 'GPQ-260035', 'routeKey' => 'activeAirport', 'warehouseKey' => 'apodaca', 'senderKey' => 'textiles', 'recipientKey' => 'hospital', 'addressKey' => 'hospitalMain', 'packageType' => 'Electronica', 'weight' => 144.0, 'quantity' => 4, 'volume' => 0.1000, 'status' => 'En ruta', 'priority' => 'standard', 'declaredValue' => 12500.0, 'description' => 'Pantallas y perifricos de control medico en reparto.'],
            ['tracking' => 'GPQ-260036', 'routeKey' => 'activeAirport', 'warehouseKey' => 'apodaca', 'senderKey' => 'carla', 'recipientKey' => 'bioplus', 'addressKey' => 'bioplusMain', 'packageType' => 'Documentacion', 'weight' => 24.0, 'quantity' => 2, 'volume' => 0.0200, 'status' => 'Entregado', 'priority' => 'express', 'declaredValue' => 2100.0, 'description' => 'Entrega express de expediente y muestras documentales.'],
            ['tracking' => 'GPQ-260037', 'routeKey' => 'activeAirport', 'warehouseKey' => 'apodaca', 'senderKey' => 'electrohogar', 'recipientKey' => 'refacciones', 'addressKey' => 'refaccionesMain', 'packageType' => 'Carga general', 'weight' => 174.0, 'quantity' => 5, 'volume' => 0.1300, 'status' => 'En ruta', 'priority' => 'standard', 'declaredValue' => 9600.0, 'description' => 'Reabasto parcial de accesorios para canal industrial.'],
            ['tracking' => 'GPQ-260038', 'routeKey' => 'completedCentral', 'warehouseKey' => 'mty', 'senderKey' => 'hospital', 'recipientKey' => 'carla', 'addressKey' => 'carlaOffice', 'packageType' => 'Documentacion', 'weight' => 19.0, 'quantity' => 2, 'volume' => 0.0200, 'status' => 'Entregado', 'priority' => 'express', 'declaredValue' => 2300.0, 'description' => 'Expediente de alta y materiales de archivo completados.'],
            ['tracking' => 'GPQ-260039', 'routeKey' => 'completedCentral', 'warehouseKey' => 'mty', 'senderKey' => 'autopartes', 'recipientKey' => 'farmacia', 'addressKey' => 'farmaciaMain', 'packageType' => 'Carga general', 'weight' => 146.0, 'quantity' => 4, 'volume' => 0.1100, 'status' => 'Entregado', 'priority' => 'standard', 'declaredValue' => 8400.0, 'description' => 'Insumos secundarios cerrados en entrega historica.'],
            ['tracking' => 'GPQ-260040', 'routeKey' => 'completedCentral', 'warehouseKey' => 'mty', 'senderKey' => 'libreria', 'recipientKey' => 'electrohogar', 'addressKey' => 'electrohogarMain', 'packageType' => 'Documentacion', 'weight' => 26.0, 'quantity' => 3, 'volume' => 0.0200, 'status' => 'Entregado', 'priority' => 'standard', 'declaredValue' => 1800.0, 'description' => 'Documentos de surtido y devolucion cerrados en historico.'],
            ['tracking' => 'GPQ-260041', 'routeKey' => 'completedCentral', 'warehouseKey' => 'mty', 'senderKey' => 'bioplus', 'recipientKey' => 'textiles', 'addressKey' => 'textilesMain', 'packageType' => 'Electronica', 'weight' => 132.0, 'quantity' => 3, 'volume' => 0.0900, 'status' => 'Entregado', 'priority' => 'high', 'declaredValue' => 14100.0, 'description' => 'Lectores de laboratorio entregados en corte historico.'],
            ['tracking' => 'GPQ-260042', 'routeKey' => null, 'warehouseKey' => 'santaCatarina', 'senderKey' => 'hospital', 'recipientKey' => 'carla', 'addressKey' => 'carlaHome', 'packageType' => 'Documentacion', 'weight' => 16.0, 'quantity' => 1, 'volume' => 0.0200, 'status' => 'Registrado', 'priority' => 'standard', 'declaredValue' => 1400.0, 'description' => 'Sobre clinico registrado pendiente de corte.'],
            ['tracking' => 'GPQ-260043', 'routeKey' => null, 'warehouseKey' => 'saltillo', 'senderKey' => 'autopartes', 'recipientKey' => 'farmacia', 'addressKey' => 'farmaciaMain', 'packageType' => 'Carga general', 'weight' => 152.0, 'quantity' => 4, 'volume' => 0.1000, 'status' => 'Registrado', 'priority' => 'standard', 'declaredValue' => 7200.0, 'description' => 'Reabasto regional registrado en cola de despacho.'],
        ];

        $shipmentIds = [];
        $seededShipments = [];
        $routeSequence = [];

        foreach ($shipmentBlueprints as $index => $blueprint) {
            $route = $blueprint['routeKey'] ? $routeMeta[$blueprint['routeKey']] : null;
            $scheduledDate = $route ? $route['scheduledDate']->copy() : $tomorrow->copy();

            if ($blueprint['routeKey'] === 'completedWest') {
                $scheduledDate = $yesterday->copy();
            } elseif ($blueprint['routeKey'] === 'completedSaltillo') {
                $scheduledDate = $twoDaysAgo->copy();
            } elseif ($blueprint['routeKey'] === 'completedCentral') {
                $scheduledDate = $threeDaysAgo->copy();
            }

            $createdAt = $scheduledDate->copy()->subDays($route && $route['status'] === 'Preparacion' ? 1 : 2)->setTime(9 + ($index % 4), 10 + (($index * 7) % 40));
            $assignedAt = $route ? ($route['status'] === 'Preparacion'
                ? $today->copy()->setTime(16, 5 + (($index % 4) * 8))
                : $scheduledDate->copy()->setTime(7, 50 + (($index % 4) * 7))) : null;
            $pickupTime = $route && $route['status'] !== 'Preparacion'
                ? $scheduledDate->copy()->setTime(8, 20 + (($index % 4) * 6))
                : null;
            $deliveryTime = $blueprint['status'] === 'Entregado'
                ? $scheduledDate->copy()->setTime(11 + ($index % 4), 10 + (($index * 6) % 40))
                : null;
            $etaAt = $scheduledDate->copy()->setTime(15 + ($index % 3), 15);
            $destination = $locationCatalog[$blueprint['addressKey']];
            $shipmentId = $this->upsertAndGetId('paquetes', ['codigo_tracking' => $blueprint['tracking']], [
                'cliente_id' => $clientIds[$blueprint['senderKey']],
                'tipo_id' => (int) $packageTypes[$blueprint['packageType']],
                'peso' => $blueprint['weight'],
                'peso_kg' => $blueprint['weight'],
                'volumen' => $blueprint['volume'],
                'volumen_m3' => $blueprint['volume'],
                'direccion_origen_id' => $locationCatalog[$warehouseAddressKeys[$blueprint['warehouseKey']]]['direccion_id'],
                'origen_direccion_id' => $locationCatalog[$warehouseAddressKeys[$blueprint['warehouseKey']]]['direccion_id'],
                'direccion_destino_id' => $destination['direccion_id'],
                'destino_direccion_id' => $destination['direccion_id'],
                'estado_id' => (int) $packageStatuses[$blueprint['status']],
                'estado' => $blueprint['status'],
                'descripcion' => $blueprint['description'],
                'fecha_estimada_entrega' => $etaAt,
                'tracking_code' => $blueprint['tracking'],
                'sender_id' => $clientIds[$blueprint['senderKey']],
                'recipient_id' => $clientIds[$blueprint['recipientKey']],
                'recipient_address_id' => $customerAddressIds[$blueprint['addressKey']] ?? null,
                'origin_warehouse_id' => $warehouseIds[$blueprint['warehouseKey']],
                'weight_kg' => $blueprint['weight'],
                'quantity' => $blueprint['quantity'],
                'volume_m3' => $blueprint['volume'],
                'package_type' => $blueprint['packageType'],
                'description' => $blueprint['description'],
                'declared_value' => $blueprint['declaredValue'],
                'recipient_address' => $destination['address'],
                'recipient_city' => $destination['city'],
                'recipient_state' => $destination['state'],
                'recipient_postal_code' => $destination['postal_code'],
                'recipient_latitude' => $destination['latitude'],
                'recipient_longitude' => $destination['longitude'],
                'status' => $blueprint['status'],
                'priority' => $blueprint['priority'],
                'scheduled_date' => $scheduledDate->toDateString(),
                'assigned_at' => $assignedAt,
                'eta_at' => $etaAt,
                'promised_date' => $scheduledDate->toDateString(),
                'pickup_time' => $pickupTime,
                'delivery_time' => $deliveryTime,
                'attempts' => $blueprint['status'] === 'Entregado' ? 1 : 0,
                'notes' => 'Registro ampliado del dataset demo GESTIONPAQ.',
                'codigo_rastreo' => $blueprint['tracking'],
                'created_at' => $createdAt,
                'updated_at' => $now,
            ]);

            $shipmentIds[] = $shipmentId;
            $seededShipments[] = [
                'id' => $shipmentId,
                'tracking' => $blueprint['tracking'],
                'routeKey' => $blueprint['routeKey'],
                'warehouseKey' => $blueprint['warehouseKey'],
                'recipientKey' => $blueprint['recipientKey'],
                'addressKey' => $blueprint['addressKey'],
                'status' => $blueprint['status'],
                'createdAt' => $createdAt,
                'assignedAt' => $assignedAt,
                'pickupTime' => $pickupTime,
                'deliveryTime' => $deliveryTime,
            ];

            if ($route) {
                $routeSequence[$blueprint['routeKey']] = ($routeSequence[$blueprint['routeKey']] ?? 0) + 1;

                DB::table('asignaciones')->updateOrInsert(
                    ['package_id' => $shipmentId],
                    [
                        'ruta_id' => $route['id'],
                        'route_id' => $route['id'],
                        'vehiculo_id' => $route['vehicleId'],
                        'vehicle_id' => $route['vehicleId'],
                        'conductor_id' => $route['driverId'],
                        'driver_id' => $route['driverId'],
                        'warehouse_id' => $route['warehouseId'],
                        'sequence_order' => $routeSequence[$blueprint['routeKey']],
                        'status' => $blueprint['status'] === 'Entregado' ? 'delivered' : 'assigned',
                        'dispatcher_user_id' => $userIds['dispatcher'],
                        'fecha_asignacion' => $assignedAt,
                        'fecha_salida' => $pickupTime,
                        'fecha_llegada_estimada' => $etaAt,
                        'estado' => $blueprint['status'] === 'Entregado' ? 'entregada' : ($blueprint['status'] === 'En ruta' ? 'en curso' : 'programada'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        $maintenanceDefinitions = [
            ['vehicleKey' => 'urbanVan', 'type' => 'Preventivo', 'scheduledDate' => $today->copy()->subDays(13)->toDateString(), 'completionDate' => $today->copy()->subDays(12)->toDateString(), 'status' => 'completed', 'cost' => 2850.0, 'km' => 13610.0, 'description' => 'Cambio de aceite, revision general y ajuste ligero.'],
            ['vehicleKey' => 'intercityTruck', 'type' => 'Inspeccion', 'scheduledDate' => $tomorrow->toDateString(), 'completionDate' => null, 'status' => 'scheduled', 'cost' => 1900.0, 'km' => 28620.0, 'description' => 'Inspeccion preoperativa para corredor interurbano.'],
            ['vehicleKey' => 'mtyReserve', 'type' => 'Preventivo', 'scheduledDate' => $today->copy()->subDays(6)->toDateString(), 'completionDate' => $today->copy()->subDays(5)->toDateString(), 'status' => 'completed', 'cost' => 2100.0, 'km' => 9310.0, 'description' => 'Servicio menor y validacion de frenos.'],
            ['vehicleKey' => 'airportReserve', 'type' => 'Llantas', 'scheduledDate' => $dayAfterTomorrow->toDateString(), 'completionDate' => null, 'status' => 'scheduled', 'cost' => 3600.0, 'km' => 17380.0, 'description' => 'Rotacion y balanceo preventivo de neumaticos.'],
            ['vehicleKey' => 'saltilloTrailer', 'type' => 'Correctivo', 'scheduledDate' => $today->copy()->subDay()->toDateString(), 'completionDate' => null, 'status' => 'in_progress', 'cost' => 6240.0, 'km' => 40280.0, 'description' => 'Revision de sistema electrico y conectores de caja.'],
            ['vehicleKey' => 'westSupport', 'type' => 'Preventivo', 'scheduledDate' => $today->copy()->subDays(17)->toDateString(), 'completionDate' => $today->copy()->subDays(16)->toDateString(), 'status' => 'completed', 'cost' => 2480.0, 'km' => 12680.0, 'description' => 'Afinacion preventiva y cambio de filtros.'],
            ['vehicleKey' => 'apodacaRunner', 'type' => 'Inspeccion', 'scheduledDate' => $today->copy()->subDays(5)->toDateString(), 'completionDate' => $today->copy()->subDays(4)->toDateString(), 'status' => 'completed', 'cost' => 1750.0, 'km' => 8610.0, 'description' => 'Inspeccion de seguridad y chequeo de niveles.'],
        ];

        foreach ($maintenanceDefinitions as $definition) {
            DB::table('mantenimiento')->updateOrInsert(
                ['vehicle_id' => $vehicleIds[$definition['vehicleKey']], 'scheduled_date' => $definition['scheduledDate'], 'type' => $definition['type']],
                [
                    'vehiculo_id' => $vehicleIds[$definition['vehicleKey']],
                    'tipo_id' => (int) $maintenanceTypes[$definition['type']],
                    'fecha' => $definition['scheduledDate'],
                    'costo' => $definition['cost'],
                    'descripcion' => $definition['description'],
                    'description' => $definition['description'],
                    'cost' => $definition['cost'],
                    'completion_date' => $definition['completionDate'],
                    'km_at_maintenance' => $definition['km'],
                    'status' => $definition['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $deliveredShipments = array_values(array_filter($seededShipments, fn ($shipment) => $shipment['status'] === 'Entregado'));

        foreach (array_slice($deliveredShipments, 0, 12) as $shipment) {
            $assignmentId = (int) DB::table('asignaciones')->where('package_id', $shipment['id'])->value('id');
            $route = $shipment['routeKey'] ? $routeMeta[$shipment['routeKey']] : null;

            if (! $assignmentId || ! $route || empty($route['driverId'])) {
                continue;
            }

            $recipientName = $clientNames[$shipment['recipientKey']] ?? 'Cliente receptor';
            DB::table('evidencias')->updateOrInsert(
                ['package_id' => $shipment['id']],
                [
                    'asignacion_id' => $assignmentId,
                    'driver_id' => $route['driverId'],
                    'route_id' => $route['id'],
                    'delivery_timestamp' => $shipment['deliveryTime'],
                    'recipient_name' => $recipientName,
                    'signature_path' => '/uploads/evidences/signatures/demo-signature-gpq.svg',
                    'photo_path' => '/uploads/evidences/photos/demo-delivery-gpq.svg',
                    'gps_latitude' => $locationCatalog[$shipment['addressKey']]['latitude'],
                    'gps_longitude' => $locationCatalog[$shipment['addressKey']]['longitude'],
                    'notes' => 'Evidencia automatica del dataset ampliado GESTIONPAQ.',
                    'status' => 'delivered',
                    'url_imagen' => '/uploads/evidences/photos/demo-delivery-gpq.svg',
                    'firma' => $recipientName,
                    'fecha' => $shipment['deliveryTime'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DB::table('tracking')
            ->where(function ($query) use ($shipmentIds): void {
                $query->whereIn('package_id', $shipmentIds)
                    ->orWhereIn('paquete_id', $shipmentIds);
            })
            ->delete();

        foreach ($seededShipments as $shipment) {
            $warehouseLocation = $locationCatalog[$warehouseAddressKeys[$shipment['warehouseKey']]];
            $destinationLocation = $locationCatalog[$shipment['addressKey']];

            LogisticsSupport::recordTrackingEvent(
                $shipment['id'],
                'Registro',
                'Envio registrado en la mesa operativa ampliada.',
                $warehouseLocation['reference'],
                'Registrado',
                $warehouseLocation['latitude'],
                $warehouseLocation['longitude'],
                $shipment['createdAt']
            );

            if ($shipment['assignedAt']) {
                LogisticsSupport::recordTrackingEvent(
                    $shipment['id'],
                    'Asignacion',
                    'Envio asignado a una corrida operativa del plan diario.',
                    $warehouseLocation['reference'],
                    in_array($shipment['status'], ['Pendiente', 'Registrado'], true) ? 'Pendiente' : $shipment['status'],
                    $warehouseLocation['latitude'],
                    $warehouseLocation['longitude'],
                    $shipment['assignedAt']
                );
            }

            if ($shipment['pickupTime']) {
                LogisticsSupport::recordTrackingEvent(
                    $shipment['id'],
                    'Salida',
                    'Unidad salida a reparto conforme al corte operativo.',
                    $warehouseLocation['city'],
                    'En ruta',
                    $warehouseLocation['latitude'],
                    $warehouseLocation['longitude'],
                    $shipment['pickupTime']
                );
            }

            if ($shipment['status'] === 'En ruta') {
                LogisticsSupport::recordTrackingEvent(
                    $shipment['id'],
                    'Ultima milla',
                    'Entrega en recorrido final hacia el destino programado.',
                    $destinationLocation['city'],
                    'En ruta',
                    $destinationLocation['latitude'],
                    $destinationLocation['longitude'],
                    $shipment['pickupTime'] ? $shipment['pickupTime']->copy()->addHours(2) : $shipment['assignedAt']->copy()->addHours(3)
                );
            }

            if ($shipment['status'] === 'Entregado' && $shipment['deliveryTime']) {
                LogisticsSupport::recordTrackingEvent(
                    $shipment['id'],
                    'Entrega',
                    'Entrega confirmada con evidencia de cierre.',
                    $destinationLocation['reference'],
                    'Entregado',
                    $destinationLocation['latitude'],
                    $destinationLocation['longitude'],
                    $shipment['deliveryTime']
                );
            }
        }

        foreach ($routeIds as $routeId) {
            LogisticsPlanner::syncRouteMetrics($routeId);
        }
    }

    private function upsertUser(string $email, string $legacyEmail, int $roleId, string $password): int
    {
        $user = DB::table('usuarios')
            ->whereIn('email', [$email, $legacyEmail])
            ->first();

        $insertPayload = [
            'email' => $email,
            'password' => Hash::make($password),
            'rol_id' => $roleId,
            'activo' => 1,
            'api_token' => null,
            'remember_token' => null,
            'last_login_at' => null,
        ];

        if ($user) {
            // Preservar api_token para no invalidar sesiones activas al re-sembrar
            $updatePayload = $insertPayload;
            unset($updatePayload['api_token'], $updatePayload['remember_token'], $updatePayload['last_login_at']);
            DB::table('usuarios')->where('id', $user->id)->update($updatePayload);

            return (int) $user->id;
        }

        return (int) DB::table('usuarios')->insertGetId($insertPayload);
    }

    private function upsertLinkedPerson(int $userId, string $email, string $legacyEmail, array $person): int
    {
        $record = DB::table('personas')
            ->where('usuario_id', $userId)
            ->orWhereIn('email', [$email, $legacyEmail])
            ->first();

        $payload = [
            'usuario_id' => $userId,
            'nombre' => $person['nombre'],
            'apellido_paterno' => $person['apellido_paterno'],
            'apellido_materno' => $person['apellido_materno'],
            'nombres' => $person['nombre'],
            'apellidos' => trim($person['apellido_paterno'].' '.$person['apellido_materno']),
            'telefono' => $person['telefono'],
            'documento' => $person['documento'],
            'email' => $email,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($record) {
            DB::table('personas')->where('id', $record->id)->update($payload);

            return (int) $record->id;
        }

        return (int) DB::table('personas')->insertGetId($payload);
    }

    private function upsertStandalonePerson(string $email, array $person): int
    {
        $record = DB::table('personas')->where('email', $email)->first();

        $payload = [
            'usuario_id' => null,
            'nombre' => $person['nombre'],
            'apellido_paterno' => $person['apellido_paterno'],
            'apellido_materno' => $person['apellido_materno'],
            'nombres' => $person['nombre'],
            'apellidos' => trim($person['apellido_paterno'].' '.$person['apellido_materno']),
            'telefono' => $person['telefono'],
            'documento' => $person['documento'],
            'email' => $email,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($record) {
            DB::table('personas')->where('id', $record->id)->update($payload);

            return (int) $record->id;
        }

        return (int) DB::table('personas')->insertGetId($payload);
    }

    private function upsertClient(string $code, ?int $personId, array $payload): int
    {
        $record = DB::table('clientes')
            ->where('codigo_cliente', $code)
            ->when($personId, fn ($query) => $query->orWhere('persona_id', $personId))
            ->first();

        $values = array_merge($payload, [
            'persona_id' => $personId,
            'codigo_cliente' => $code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($record) {
            DB::table('clientes')->where('id', $record->id)->update($values);

            return (int) $record->id;
        }

        return (int) DB::table('clientes')->insertGetId($values);
    }

    private function upsertDriver(int $personId, array $payload): int
    {
        $record = DB::table('conductores')->where('persona_id', $personId)->first();

        $values = array_merge($payload, ['persona_id' => $personId]);

        if ($record) {
            DB::table('conductores')->where('id', $record->id)->update($values);

            return (int) $record->id;
        }

        return (int) DB::table('conductores')->insertGetId($values);
    }

    private function upsertAddress(string $reference, array $payload): int
    {
        return $this->upsertAndGetId('direcciones', ['referencia' => $reference], array_merge($payload, [
            'referencia' => $reference,
            'pais' => 'Mexico',
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function upsertCustomerAddress(int $clientId, string $label, array $payload): int
    {
        return $this->upsertAndGetId('cliente_direcciones', ['cliente_id' => $clientId, 'label' => $label], array_merge($payload, [
            'cliente_id' => $clientId,
            'label' => $label,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function upsertAndGetId(string $table, array $match, array $payload): int
    {
        DB::table($table)->updateOrInsert($match, $payload);

        return (int) DB::table($table)->where($match)->value('id');
    }
}