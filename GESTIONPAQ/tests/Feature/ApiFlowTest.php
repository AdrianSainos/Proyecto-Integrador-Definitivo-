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
        $sender = DB::table('clientes')->orderBy('id')->first();
        $recipient = DB::table('clientes')->where('id', '<>', $sender->id)->orderBy('id')->first();
        $recipientAddress = DB::table('cliente_direcciones')
            ->where('cliente_id', $recipient->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
        $warehouse = DB::table('almacenes')->orderBy('id')->first();
        $packageType = (string) (DB::table('tipo_paquete')->orderBy('id')->value('nombre') ?: 'Caja');
        $scheduledDate = Carbon::now()->addDays(4)->toDateString();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])->postJson('/api/shipments', [
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
        ]);

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
}
