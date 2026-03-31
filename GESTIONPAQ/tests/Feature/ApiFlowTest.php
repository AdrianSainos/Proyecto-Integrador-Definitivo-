<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $compiledViews = base_path('tests/.runtime/views');
        $dompdfTemp = base_path('tests/.runtime/dompdf');

        if (! is_dir($compiledViews)) {
            mkdir($compiledViews, 0777, true);
        }

        if (! is_dir($dompdfTemp)) {
            mkdir($dompdfTemp, 0777, true);
        }

        config([
            'view.compiled' => $compiledViews,
            'dompdf.temp_dir' => $dompdfTemp,
        ]);
    }

    public function test_admin_can_login_and_fetch_profile(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@gestionpaq.local',
            'password' => 'admin123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'email', 'role', 'name', 'active'],
            ]);

        $token = (string) $response->json('token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@gestionpaq.local')
            ->assertJsonPath('user.role', 'admin');
    }

    public function test_future_shipment_is_planified_with_route_driver_and_shift(): void
    {
        $token = $this->authenticateAdmin();
        $scheduledDate = Carbon::now()->addDays(4)->toDateString();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])->postJson(
            '/api/shipments',
            $this->buildShipmentPayload($scheduledDate, ['priority' => 'express'])
        );

        $response->assertCreated()->assertJsonPath('item.status', 'Planificado');

        $shipmentId = (int) $response->json('item.id');
        $driverId = (int) $response->json('item.driverId');
        $routeId = (int) $response->json('item.routeId');

        $this->assertStringStartsWith('GPQ-', (string) $response->json('item.tracking'));
        $this->assertGreaterThan(0, $driverId);
        $this->assertGreaterThan(0, $routeId);
        $this->assertNotSame('Pendiente', $response->json('item.driverName'));

        $this->assertDatabaseHas('paquetes', [
            'id' => $shipmentId,
            'estado' => 'Planificado',
        ]);

        $this->assertDatabaseHas('asignaciones', [
            'package_id' => $shipmentId,
            'ruta_id' => $routeId,
            'driver_id' => $driverId,
        ]);

        $this->assertDatabaseHas('turnos_conductor', [
            'driver_id' => $driverId,
            'shift_date' => $scheduledDate,
        ]);
    }

    public function test_same_day_shipment_is_assigned_with_route_driver_and_shift(): void
    {
        $token = $this->authenticateAdmin();
        $scheduledDate = Carbon::now()->toDateString();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])->postJson(
            '/api/shipments',
            $this->buildShipmentPayload($scheduledDate, ['priority' => 'standard'])
        );

        $response->assertCreated();

        $this->assertContains($response->json('item.status'), ['Asignado', 'En ruta']);

        $driverId = (int) $response->json('item.driverId');
        $routeId = (int) $response->json('item.routeId');

        $this->assertGreaterThan(0, $driverId);
        $this->assertGreaterThan(0, $routeId);
        $this->assertDatabaseHas('turnos_conductor', [
            'driver_id' => $driverId,
            'shift_date' => $scheduledDate,
        ]);
    }

    public function test_explicit_pending_shipment_stays_pending_without_assignment(): void
    {
        $token = $this->authenticateAdmin();
        $scheduledDate = Carbon::now()->addDays(4)->toDateString();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])->postJson(
            '/api/shipments',
            $this->buildShipmentPayload($scheduledDate, [
                'priority' => 'standard',
                'initialStatus' => 'Pendiente',
            ])
        );

        $response->assertCreated()
            ->assertJsonPath('item.status', 'Pendiente')
            ->assertJsonPath('item.routeCode', 'Pendiente');

        $shipmentId = (int) $response->json('item.id');

        $this->assertNull($response->json('item.routeId'));
        $this->assertDatabaseHas('paquetes', [
            'id' => $shipmentId,
            'estado' => 'Pendiente',
        ]);
        $this->assertDatabaseMissing('asignaciones', [
            'package_id' => $shipmentId,
        ]);
    }

    public function test_future_shipment_creates_planned_route_even_when_resources_are_pending_confirmation(): void
    {
        $token = $this->authenticateAdmin();
        $scheduledDate = Carbon::now()->addDays(6)->toDateString();
        $warehouseId = (int) DB::table('almacenes')->orderBy('id')->value('id');

        DB::table('rutas')
            ->where(function ($query) use ($warehouseId): void {
                $query->where('warehouse_id', $warehouseId)
                    ->orWhere('almacen_origen_id', $warehouseId)
                    ->orWhere('origen_almacen_id', $warehouseId);
            })
            ->update([
                'status' => 'Completada',
                'estado' => 'Completada',
            ]);

        DB::table('vehiculos')
            ->where('warehouse_id', $warehouseId)
            ->update(['activo' => 0]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])->postJson(
            '/api/shipments',
            $this->buildShipmentPayload($scheduledDate, [
                'originWarehouseId' => $warehouseId,
                'priority' => 'standard',
            ])
        );

        $response->assertCreated()->assertJsonPath('item.status', 'Planificado');

        $shipmentId = (int) $response->json('item.id');
        $routeId = (int) $response->json('item.routeId');

        $this->assertGreaterThan(0, $routeId);
        $this->assertStringStartsWith('GPQ-R-', (string) $response->json('item.routeCode'));
        $this->assertStringNotContainsString('AUTO-', (string) $response->json('item.routeCode'));
        $this->assertSame('Pendiente', $response->json('item.vehiclePlate'));
        $this->assertSame('Pendiente', $response->json('item.driverName'));

        $this->assertDatabaseHas('asignaciones', [
            'package_id' => $shipmentId,
            'ruta_id' => $routeId,
        ]);

        $this->assertDatabaseHas('rutas', [
            'id' => $routeId,
            'status' => 'Preparacion',
        ]);
    }

    public function test_future_shipment_prefers_existing_compatible_route_before_creating_a_new_one(): void
    {
        $token = $this->authenticateAdmin();
        $scheduledDate = Carbon::now()->addDays(5)->toDateString();
        $payload = $this->buildShipmentPayload($scheduledDate, ['priority' => 'standard', 'weightKg' => 42.0, 'quantity' => 2]);
        $warehouseId = (int) $payload['originWarehouseId'];
        $warehouse = DB::table('almacenes')->where('id', $warehouseId)->first();
        $vehicle = DB::table('vehiculos')
            ->where('warehouse_id', $warehouseId)
            ->where(function ($query): void {
                $query->whereNull('activo')->orWhere('activo', 1);
            })
            ->orderBy('id')
            ->first();

        $this->assertNotNull($warehouse);
        $this->assertNotNull($vehicle);

        DB::table('vehiculos')->where('id', $vehicle->id)->update([
            'status' => 'Operativo',
            'estado' => 'Operativo',
            'current_fuel' => max(90, (float) ($vehicle->current_fuel ?? 0)),
            'fuel_consumption_km' => max(0.08, (float) ($vehicle->fuel_consumption_km ?? 0.1)),
            'capacity_kg' => max(1200, (float) ($vehicle->capacity_kg ?? $vehicle->capacidad_kg ?? 0)),
            'capacity_packages' => max(60, (int) ($vehicle->capacity_packages ?? 0)),
        ]);

        DB::table('rutas')
            ->where(function ($query) use ($warehouseId): void {
                $query->where('warehouse_id', $warehouseId)
                    ->orWhere('almacen_origen_id', $warehouseId)
                    ->orWhere('origen_almacen_id', $warehouseId);
            })
            ->whereDate('scheduled_date', $scheduledDate)
            ->update([
                'status' => 'Completada',
                'estado' => 'Completada',
            ]);

        $routeId = DB::table('rutas')->insertGetId([
            'codigo' => 'TEST-R-EXISTENTE',
            'route_code' => 'TEST-R-EXISTENTE',
            'almacen_origen_id' => $warehouseId,
            'origen_almacen_id' => $warehouseId,
            'warehouse_id' => $warehouseId,
            'destino_almacen_id' => null,
            'distancia_km' => 18.4,
            'estimated_distance_km' => 18.4,
            'tiempo_estimado_min' => 55,
            'estimated_time_minutes' => 55,
            'vehicle_id' => $vehicle->id,
            'driver_id' => null,
            'scheduled_date' => $scheduledDate,
            'start_time' => null,
            'end_time' => null,
            'total_packages' => 0,
            'total_weight_kg' => 0,
            'actual_distance_km' => 0,
            'actual_time_minutes' => 0,
            'fuel_consumed_liters' => 0,
            'status' => 'Preparacion',
            'estado' => 'Preparacion',
            'estado_id' => DB::table('estado_ruta')->where('nombre', 'Preparacion')->value('id'),
            'optimization_score' => 97.5,
            'waypoints' => json_encode([
                [
                    'label' => (string) ($warehouse->nombre ?? $warehouse->code ?? $warehouse->codigo ?? 'Origen'),
                    'lat' => (float) ($warehouse->latitude ?? 0),
                    'lng' => (float) ($warehouse->longitude ?? 0),
                ],
                [
                    'label' => (string) $payload['destinationAddress'],
                    'lat' => (float) DB::table('cliente_direcciones')->where('id', $payload['destinationAddressId'])->value('latitude'),
                    'lng' => (float) DB::table('cliente_direcciones')->where('id', $payload['destinationAddressId'])->value('longitude'),
                ],
            ], JSON_UNESCAPED_SLASHES),
            'notes' => 'Ruta existente para validar preferencia del planner.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])->postJson('/api/shipments', $payload);

        $response->assertCreated()
            ->assertJsonPath('item.status', 'Planificado')
            ->assertJsonPath('item.routeId', $routeId)
            ->assertJsonPath('item.routeCode', 'TEST-R-EXISTENTE');

        $this->assertDatabaseHas('asignaciones', [
            'ruta_id' => $routeId,
            'package_id' => (int) $response->json('item.id'),
        ]);
    }

    public function test_shipment_rejects_same_sender_and_recipient(): void
    {
        $token = $this->authenticateAdmin();
        $sender = DB::table('clientes')->orderBy('id')->first();
        $senderAddress = DB::table('cliente_direcciones')
            ->where('cliente_id', $sender->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        $payload = $this->buildShipmentPayload(Carbon::now()->addDay()->toDateString(), [
            'senderId' => (int) $sender->id,
            'recipientId' => (int) $sender->id,
            'destinationAddressId' => (int) ($senderAddress->id ?? 0),
            'destinationAddress' => (string) ($senderAddress->address ?? $sender->default_address),
            'destinationCity' => (string) ($senderAddress->city ?? 'Ciudad'),
            'destinationState' => (string) ($senderAddress->state ?? 'Estado'),
            'destinationPostalCode' => (string) ($senderAddress->postal_code ?? '00000'),
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/shipments', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recipientId']);
    }

    public function test_route_delete_releases_assignments_and_restores_shipment_status(): void
    {
        $token = $this->authenticateAdmin();
        $scheduledDate = Carbon::now()->addDays(3)->toDateString();

        $createResponse = $this->withHeaders(['Authorization' => 'Bearer '.$token])->postJson(
            '/api/shipments',
            $this->buildShipmentPayload($scheduledDate, ['priority' => 'express'])
        );

        $createResponse->assertCreated();

        $shipmentId = (int) $createResponse->json('item.id');
        $routeId = (int) $createResponse->json('item.routeId');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->deleteJson('/api/routes/'.$routeId)
            ->assertNoContent();

        $this->assertDatabaseMissing('rutas', ['id' => $routeId]);
        $this->assertDatabaseMissing('asignaciones', ['package_id' => $shipmentId]);
        $this->assertDatabaseHas('paquetes', [
            'id' => $shipmentId,
            'estado' => 'Planificado',
        ]);
    }

    public function test_driver_delete_is_blocked_when_driver_has_assignments(): void
    {
        $token = $this->authenticateAdmin();
        $scheduledDate = Carbon::now()->addDays(2)->toDateString();

        $createResponse = $this->withHeaders(['Authorization' => 'Bearer '.$token])->postJson(
            '/api/shipments',
            $this->buildShipmentPayload($scheduledDate, ['priority' => 'express'])
        );

        $createResponse->assertCreated();

        $driverId = (int) $createResponse->json('item.driverId');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->deleteJson('/api/drivers/'.$driverId)
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar el conductor mientras tenga rutas o asignaciones asociadas. Reasignalo primero.');
    }

    public function test_vehicle_delete_is_blocked_when_vehicle_has_operational_references(): void
    {
        $token = $this->authenticateAdmin();
        $scheduledDate = Carbon::now()->addDays(2)->toDateString();

        $createResponse = $this->withHeaders(['Authorization' => 'Bearer '.$token])->postJson(
            '/api/shipments',
            $this->buildShipmentPayload($scheduledDate, ['priority' => 'express'])
        );

        $createResponse->assertCreated();

        $vehicleId = (int) $createResponse->json('item.vehicleId');

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->deleteJson('/api/vehicles/'.$vehicleId);

        $response->assertStatus(422);
        $this->assertStringContainsString('No se puede eliminar el vehiculo porque aun tiene referencias en', (string) $response->json('message'));
        $this->assertStringContainsString('rutas', (string) $response->json('message'));
    }

    public function test_admin_can_download_csv_report(): void
    {
        $token = $this->authenticateAdmin();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->get('/api/reports/export/csv?range=7d');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
    }

    public function test_admin_can_download_pdf_report(): void
    {
        $token = $this->authenticateAdmin();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->get('/api/reports/export/pdf?range=7d');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
    }

    private function authenticateAdmin(): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@gestionpaq.local',
            'password' => 'admin123',
        ]);

        $response->assertOk();

        return (string) $response->json('token');
    }

    private function buildShipmentPayload(string $scheduledDate, array $overrides = []): array
    {
        $sender = DB::table('clientes')->orderBy('id')->first();
        $recipient = DB::table('clientes')->where('id', '<>', $sender->id)->orderBy('id')->first();
        $recipientAddress = DB::table('cliente_direcciones')
            ->where('cliente_id', $recipient->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
        $warehouse = DB::table('almacenes')->orderBy('id')->first();
        $packageType = (string) (DB::table('tipo_paquete')->orderBy('id')->value('nombre') ?: 'Caja');

        return array_merge([
            'senderId' => (int) $sender->id,
            'recipientId' => (int) $recipient->id,
            'originWarehouseId' => (int) $warehouse->id,
            'originAddress' => trim(implode(', ', array_filter([$warehouse->address ?? null, $warehouse->city ?? null, $warehouse->state ?? null]))),
            'weightKg' => 85.5,
            'quantity' => 4,
            'volumeM3' => 1.8,
            'scheduledDate' => $scheduledDate,
            'packageType' => $packageType,
            'priority' => 'express',
            'description' => 'Envio de prueba con planeacion automatica.',
            'destinationAddressId' => (int) $recipientAddress->id,
            'destinationAddress' => (string) $recipientAddress->address,
            'destinationCity' => (string) $recipientAddress->city,
            'destinationState' => (string) $recipientAddress->state,
            'destinationPostalCode' => (string) $recipientAddress->postal_code,
        ], $overrides);
    }
}
