<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LogisticsNationalCoverageSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $today = $now->copy()->startOfDay();
            $tomorrow = $today->copy()->addDay();
            $driverStatuses = DB::table('estado_conductor')->pluck('id', 'nombre');
            $vehicleStatuses = DB::table('estado_vehiculo')->pluck('id', 'nombre');
            $vehicleTypes = DB::table('tipo_vehiculo')->pluck('id', 'nombre');

            $places = [
                'warehouseCdmx' => ['Cedis Vallejo CDMX', 'Avenida Ceylan', '959', 'Industrial Vallejo', 'Azcapotzalco', 'Ciudad de Mexico', '02300', 19.49486000, -99.16443000],
                'warehouseGdl' => ['Hub Guadalajara El Salto', 'Avenida Adolf Horn', '700', 'Las Pintitas', 'El Salto', 'Jalisco', '45690', 20.55634000, -103.30655000],
                'warehouseQro' => ['Centro Queretaro 5 de Febrero', 'Avenida 5 de Febrero', '1303', 'Zona Industrial Benito Juarez', 'Queretaro', 'Queretaro', '76120', 20.61057000, -100.41108000],
                'warehousePue' => ['Crossdock Puebla Cuautlancingo', 'Autopista Mexico Puebla', '117', 'Corredor Industrial', 'Cuautlancingo', 'Puebla', '72700', 19.08828000, -98.27286000],
                'warehouseMerida' => ['Cedis Merida Poniente', 'Calle 60', '491', 'Parque Industrial', 'Merida', 'Yucatan', '97238', 20.98186000, -89.67761000],
                'warehouseTijuana' => ['Hub Tijuana Bellas Artes', 'Boulevard Bellas Artes', '17634', 'Ciudad Industrial', 'Tijuana', 'Baja California', '22444', 32.52982000, -116.94227000],
                'clientCdmx' => ['Farmacia Roma Centro Logistico', 'Calzada Camarones', '824', 'Del Recreo', 'Azcapotzalco', 'Ciudad de Mexico', '02070', 19.47886000, -99.18965000],
                'clientGdl' => ['Electro Jalisco Centro de Distribucion', 'Carretera a Chapala', '5150', 'Las Pintas', 'Tlaquepaque', 'Jalisco', '45618', 20.58969000, -103.33686000],
                'clientQro' => ['Autopartes Bajio Parque Industrial', 'Avenida Manufactura', '204', 'Parque Industrial Benito Juarez', 'Queretaro', 'Queretaro', '76120', 20.61341000, -100.40337000],
                'clientPue' => ['Textiles Angelopolis Patio Puebla', 'Boulevard Forjadores', '3105', 'Momoxpan', 'San Pedro Cholula', 'Puebla', '72760', 19.07505000, -98.27114000],
                'clientMerida' => ['Agroinsumos Peninsula Bodega', 'Calle 24', '358', 'Ciudad Industrial', 'Merida', 'Yucatan', '97288', 20.93657000, -89.59062000],
                'clientTijuana' => ['MedSupply Frontera Centro Operativo', 'Avenida Universidad', '1951', 'Otay Universidad', 'Tijuana', 'Baja California', '22427', 32.53199000, -116.97236000],
            ];

            $locations = [];
            foreach ($places as $key => [$ref, $street, $number, $neighborhood, $city, $state, $postal, $lat, $lng]) {
                $addressId = $this->upsertAddress($ref, [
                    'calle' => $street,
                    'numero' => $number,
                    'numero_ext' => $number,
                    'colonia' => $neighborhood,
                    'ciudad' => $city,
                    'estado' => $state,
                    'codigo_postal' => $postal,
                    'latitud' => $lat,
                    'longitud' => $lng,
                ]);

                $locations[$key] = [
                    'direccion_id' => $addressId,
                    'address' => $street.' '.$number,
                    'city' => $city,
                    'state' => $state,
                    'postal_code' => $postal,
                    'latitude' => $lat,
                    'longitude' => $lng,
                ];
            }

            $warehouses = [
                'cdmx' => ['ALM-GPQ-CDMX', 'CDMX-NORTE', 'Cedis Vallejo CDMX', 'warehouseCdmx', 940],
                'gdl' => ['ALM-GPQ-GDL', 'GDL-ELSALTO', 'Hub Guadalajara El Salto', 'warehouseGdl', 820],
                'qro' => ['ALM-GPQ-QRO', 'QRO-BAJIO', 'Centro Queretaro', 'warehouseQro', 760],
                'pue' => ['ALM-GPQ-PUE', 'PUE-CROSS', 'Crossdock Puebla', 'warehousePue', 720],
                'merida' => ['ALM-GPQ-MID', 'MID-PONIENTE', 'Cedis Merida Poniente', 'warehouseMerida', 640],
                'tijuana' => ['ALM-GPQ-TIJ', 'TIJ-FRONTERA', 'Hub Tijuana', 'warehouseTijuana', 880],
            ];

            $warehouseIds = [];
            foreach ($warehouses as $key => [$code, $alias, $name, $locationKey, $capacity]) {
                $location = $locations[$locationKey];
                $warehouseIds[$key] = $this->upsertAndGetId('almacenes', ['codigo' => $code], [
                    'nombre' => $name,
                    'code' => $alias,
                    'direccion_id' => $location['direccion_id'],
                    'address' => $location['address'],
                    'city' => $location['city'],
                    'state' => $location['state'],
                    'postal_code' => $location['postal_code'],
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'capacity' => $capacity,
                    'status' => 'active',
                    'activo' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $contacts = [
                'farmaciaRoma' => ['logistica@farmaciaroma.mx', '5519002101', 'CLT-GPQ-012', 'Valeria', 'Soto', 'Lopez'],
                'electroJalisco' => ['abasto@electrojalisco.mx', '3319002102', 'CLT-GPQ-013', 'Ruben', 'Lara', 'Ponce'],
                'autopartesBajio' => ['embarques@autopartesbajio.mx', '4429002103', 'CLT-GPQ-014', 'Nadia', 'Vega', 'Ruiz'],
                'textilesAngelopolis' => ['trafico@textilesangelopolis.mx', '2229002104', 'CLT-GPQ-015', 'Edgar', 'Camacho', 'Nieto'],
                'agroinsumosPeninsula' => ['operaciones@agroinsumospeninsula.mx', '9999002105', 'CLT-GPQ-016', 'Karla', 'Mendez', 'Pech'],
                'medSupplyFrontera' => ['supply@medsupplyfrontera.mx', '6649002106', 'CLT-GPQ-017', 'Oscar', 'Beltran', 'Tovar'],
            ];

            $contactIds = [];
            foreach ($contacts as $key => [$email, $phone, $document, $name, $lastName, $motherLastName]) {
                $contactIds[$key] = $this->upsertStandalonePerson($email, [
                    'nombre' => $name,
                    'apellido_paterno' => $lastName,
                    'apellido_materno' => $motherLastName,
                    'telefono' => $phone,
                    'documento' => $document,
                ]);
            }

            $clients = [
                'farmaciaRoma' => ['CLI-GPQ-012', 'farmaciaRoma', 'Farmacia Roma Centro Logistico', 'clientCdmx', 'premium', 'Reabasto urbano.'],
                'electroJalisco' => ['CLI-GPQ-013', 'electroJalisco', 'Electro Jalisco Centro de Distribucion', 'clientGdl', 'corporativo', 'Retail de electronica.'],
                'autopartesBajio' => ['CLI-GPQ-014', 'autopartesBajio', 'Autopartes Bajio', 'clientQro', 'corporativo', 'Cuenta B2B de refacciones.'],
                'textilesAngelopolis' => ['CLI-GPQ-015', 'textilesAngelopolis', 'Textiles Angelopolis', 'clientPue', 'estandar', 'Despacho recurrente a tiendas.'],
                'agroinsumosPeninsula' => ['CLI-GPQ-016', 'agroinsumosPeninsula', 'Agroinsumos Peninsula', 'clientMerida', 'estandar', 'Suministro regional.'],
                'medSupplyFrontera' => ['CLI-GPQ-017', 'medSupplyFrontera', 'MedSupply Frontera', 'clientTijuana', 'premium', 'Material medico controlado.'],
            ];

            foreach ($clients as $key => [$code, $contactKey, $name, $locationKey, $serviceLevel, $notes]) {
                [$email, $phone] = $contacts[$contactKey];
                $location = $locations[$locationKey];
                $clientId = $this->upsertClient($code, $contactIds[$contactKey], [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'identification' => $code,
                    'type' => 'business',
                    'status' => 'active',
                    'default_address' => $location['address'].', '.$location['city'].', '.$location['state'],
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'notes' => $notes,
                    'nivel_servicio' => $serviceLevel,
                    'activo' => 1,
                ]);

                $this->upsertCustomerAddress($clientId, 'principal', [
                    'direccion_id' => $location['direccion_id'],
                    'address' => $location['address'],
                    'city' => $location['city'],
                    'state' => $location['state'],
                    'postal_code' => $location['postal_code'],
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'is_default' => 1,
                ]);
            }

            $vehicles = [
                'cdmxVan' => ['GPQ-118-MX', 'cdmx', 'Transit Courier', 'Ford', 2024, 'Van', 720, 32, 46.0, 55.0, 0.092, 'Disponible', 19.49486000, -99.16443000, 'FORDGPQ118MX0001'],
                'cdmxTruck' => ['GPQ-119-MX', 'cdmx', 'Hino 300', 'Hino', 2023, 'Camion ligero', 3100, 120, 98.0, 125.0, 0.173, 'Operativo', 19.49521000, -99.16211000, 'HINOGPQ119MX0002'],
                'gdlVan' => ['GPQ-215-JL', 'gdl', 'Partner Rapid', 'Peugeot', 2024, 'Van', 650, 28, 42.0, 50.0, 0.087, 'Disponible', 20.55634000, -103.30655000, 'PEUGPQ215JL0003'],
                'qroVan' => ['GPQ-314-QR', 'qro', 'NV350', 'Nissan', 2023, 'Van', 860, 36, 48.0, 65.0, 0.104, 'Disponible', 20.61057000, -100.41108000, 'NISGPQ314QR0004'],
                'pueVan' => ['GPQ-412-PB', 'pue', 'Kangoo Maxi', 'Renault', 2023, 'Van', 790, 34, 45.0, 58.0, 0.095, 'Operativo', 19.08828000, -98.27286000, 'RNGPQ412PB0005'],
                'meridaVan' => ['GPQ-507-YN', 'merida', 'Ram ProMaster City', 'Ram', 2024, 'Van', 720, 30, 43.0, 60.0, 0.096, 'Disponible', 20.98186000, -89.67761000, 'RAMGPQ507YN0006'],
                'tijuanaVan' => ['GPQ-611-BC', 'tijuana', 'Transit Connect', 'Ford', 2022, 'Van', 760, 32, 44.0, 56.0, 0.098, 'Disponible', 32.52982000, -116.94227000, 'FORDGPQ611BC0007'],
                'tijuanaTruck' => ['GPQ-612-BC', 'tijuana', 'Cabstar', 'Nissan', 2021, 'Camion caja seca', 4700, 160, 86.0, 125.0, 0.191, 'Operativo', 32.53054000, -116.94488000, 'NISGPQ612BC0008'],
            ];

            $vehicleIds = [];
            foreach ($vehicles as $key => [$plate, $warehouseKey, $model, $brand, $year, $type, $capacityKg, $capacityPackages, $fuel, $fuelCapacity, $consumption, $status, $lat, $lng, $vin]) {
                $vehicleIds[$key] = $this->upsertAndGetId('vehiculos', ['placa' => $plate], [
                    'warehouse_id' => $warehouseIds[$warehouseKey],
                    'plate' => $plate,
                    'model' => $model,
                    'brand' => $brand,
                    'year' => $year,
                    'type' => $type,
                    'capacity_kg' => $capacityKg,
                    'capacity_packages' => $capacityPackages,
                    'current_fuel' => $fuel,
                    'fuel_capacity' => $fuelCapacity,
                    'fuel_consumption_km' => $consumption,
                    'status' => $status,
                    'last_maintenance' => $today->copy()->subDays(7)->setTime(9, 0),
                    'total_km' => 12000,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'vin' => $vin,
                    'tipo_id' => (int) ($vehicleTypes[$type] ?? 0),
                    'capacidad' => (float) $capacityKg,
                    'capacidad_kg' => $capacityKg,
                    'estado_id' => (int) ($vehicleStatuses[$status] ?? 0),
                    'estado' => $status,
                    'activo' => 1,
                    'consumo_km' => round($consumption, 2),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $drivers = [
                'fernando' => ['fernando.macias@gestionpaq.local', '5518304101', 'DRV-GPQ-012', 'Fernando', 'Macias', 'Rico', 'LIC-GPQ-2012', '2028-05-18', 'Disponible', 'cdmxVan', '1988-05-14', 'Azcapotzalco, Ciudad de Mexico'],
                'andrea' => ['andrea.sandoval@gestionpaq.local', '5518304102', 'DRV-GPQ-013', 'Andrea', 'Sandoval', 'Paz', 'LIC-GPQ-2013', '2027-11-09', 'Activo', 'cdmxTruck', '1991-09-21', 'Gustavo A Madero, Ciudad de Mexico'],
                'ismael' => ['ismael.ponce@gestionpaq.local', '3318304103', 'DRV-GPQ-014', 'Ismael', 'Ponce', 'Ledezma', 'LIC-GPQ-2014', '2028-01-16', 'Disponible', 'gdlVan', '1992-07-11', 'Tlaquepaque, Jalisco'],
                'monica' => ['monica.lozano@gestionpaq.local', '4428304104', 'DRV-GPQ-015', 'Monica', 'Lozano', 'Padilla', 'LIC-GPQ-2015', '2027-08-25', 'Disponible', 'qroVan', '1990-03-07', 'Queretaro, Queretaro'],
                'rafael' => ['rafael.montes@gestionpaq.local', '2228304105', 'DRV-GPQ-016', 'Rafael', 'Montes', 'Guerra', 'LIC-GPQ-2016', '2027-06-13', 'Activo', 'pueVan', '1987-12-04', 'Puebla, Puebla'],
                'ximena' => ['ximena.chan@gestionpaq.local', '9998304106', 'DRV-GPQ-017', 'Ximena', 'Chan', 'Baeza', 'LIC-GPQ-2017', '2028-03-30', 'Disponible', 'meridaVan', '1994-10-12', 'Merida, Yucatan'],
                'cesar' => ['cesar.ibarra@gestionpaq.local', '6648304107', 'DRV-GPQ-018', 'Cesar', 'Ibarra', 'Meza', 'LIC-GPQ-2018', '2027-09-19', 'Disponible', 'tijuanaVan', '1989-01-28', 'Tijuana, Baja California'],
                'gabriela' => ['gabriela.rosas@gestionpaq.local', '6648304108', 'DRV-GPQ-019', 'Gabriela', 'Rosas', 'Aguayo', 'LIC-GPQ-2019', '2028-04-22', 'Activo', 'tijuanaTruck', '1986-08-17', 'Tijuana, Baja California'],
            ];

            $driverIds = [];
            foreach ($drivers as $key => [$email, $phone, $document, $name, $lastName, $motherLastName, $license, $expiry, $status, $vehicleKey, $birthDate, $address]) {
                $personId = $this->upsertStandalonePerson($email, [
                    'nombre' => $name,
                    'apellido_paterno' => $lastName,
                    'apellido_materno' => $motherLastName,
                    'telefono' => $phone,
                    'documento' => $document,
                ]);

                $vehicle = $vehicles[$vehicleKey];
                $driverIds[$key] = $this->upsertDriver($personId, [
                    'numero_licencia' => $license,
                    'licencia_vence' => $expiry,
                    'activo' => 1,
                    'estado_id' => (int) ($driverStatuses[$status] ?? 0),
                    'name' => $name.' '.$lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'license_number' => $license,
                    'license_expiry' => $expiry,
                    'identification' => $document,
                    'date_of_birth' => $birthDate,
                    'address' => $address,
                    'status' => $status,
                    'current_vehicle_id' => $vehicleIds[$vehicleKey],
                    'latitude' => $vehicle[12],
                    'longitude' => $vehicle[13],
                    'last_seen_at' => $now->copy()->subMinutes(10),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($driverIds as $driverId) {
                foreach ([[$today, 'activo'], [$tomorrow, 'programado']] as [$shiftDate, $state]) {
                    DB::table('turnos_conductor')->updateOrInsert(
                        ['driver_id' => $driverId, 'shift_date' => $shiftDate->toDateString()],
                        [
                            'conductor_id' => $driverId,
                            'start_time' => '07:00:00',
                            'end_time' => '17:00:00',
                            'total_deliveries' => 0,
                            'successful_deliveries' => 0,
                            'failed_deliveries' => 0,
                            'distance_km' => 0,
                            'status' => 'scheduled',
                            'inicio_turno' => $shiftDate->copy()->setTime(7, 0),
                            'fin_turno' => null,
                            'estado' => $state,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        });
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
