<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsPlanner;
use App\Support\LogisticsSupport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EvidenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizeIndex($request)) {
            return $response;
        }

        $query = LogisticsSupport::evidenceBaseQueryFor($request)->orderByDesc('evidencias.id');

        if ($request->filled('shipmentId')) {
            $query->where('evidencias.package_id', $request->integer('shipmentId'));
        }

        if ($request->filled('tracking')) {
            $tracking = $request->string('tracking')->toString();
            $query->where(function ($filter) use ($tracking): void {
                $filter->where('paquetes.codigo_tracking', $tracking)
                    ->orWhere('paquetes.tracking_code', $tracking)
                    ->orWhere('paquetes.codigo_rastreo', $tracking);
            });
        }

        if ($request->filled('driverId')) {
            $query->where('evidencias.driver_id', $request->integer('driverId'));
        }

        if ($request->filled('status')) {
            $query->where('evidencias.status', $request->string('status')->toString());
        }

        return ApiResponder::success(
            $query->get()->map(fn ($item) => LogisticsSupport::evidencePayload($item, $request))->values()->all()
        );
    }

    public function show(Request $request, int $evidence): JsonResponse
    {
        if ($response = $this->authorizeIndex($request)) {
            return $response;
        }

        $item = LogisticsSupport::evidenceBaseQueryFor($request)->where('evidencias.id', $evidence)->first();

        return $item
            ? ApiResponder::success(LogisticsSupport::evidencePayload($item, $request))
            : ApiResponder::error('Evidencia no encontrada.', 404);
    }

    public function store(Request $request, int $shipment): JsonResponse
    {
        if ($response = $this->authorizeStore($request)) {
            return $response;
        }

        $shipmentRecord = LogisticsSupport::shipmentBaseQueryFor($request)->where('paquetes.id', $shipment)->first();

        if (! $shipmentRecord) {
            return ApiResponder::error('Envio no encontrado o no visible para tu perfil.', 404);
        }

        $payload = $request->validate([
            'recipientName' => ['required', 'string', 'max:255'],
            'deliveryTimestamp' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
            'gpsLatitude' => ['nullable', 'numeric'],
            'gpsLongitude' => ['nullable', 'numeric'],
            'photoDataUrl' => ['nullable', 'string'],
            'signatureText' => ['nullable', 'string', 'max:255'],
        ]);

        $requirePhoto = LogisticsSupport::settingBool('requirePhoto', true);
        $requireSignature = LogisticsSupport::settingBool('requireSignature', true);

        if ($requirePhoto && empty($payload['photoDataUrl'])) {
            return ApiResponder::error('La configuracion actual exige foto de entrega.', 422);
        }

        if ($requireSignature && empty($payload['signatureText'])) {
            return ApiResponder::error('La configuracion actual exige firma de entrega.', 422);
        }

        $authUser = LogisticsSupport::apiUser($request);
        $authDriverId = $this->driverIdForUser($authUser);
        $assignment = DB::table('asignaciones')->where('package_id', $shipment)->orderByDesc('id')->first();
        $routeId = (int) ($assignment->route_id ?? $assignment->ruta_id ?? $shipmentRecord->route_id ?? 0);
        $driverId = $authDriverId ?: (int) ($assignment->driver_id ?? $assignment->conductor_id ?? $shipmentRecord->driver_id ?? 0);

        if (LogisticsSupport::roleName($authUser) === 'driver' && $authDriverId && $driverId && $authDriverId !== $driverId) {
            return ApiResponder::error('No puedes registrar evidencia para un envio asignado a otro conductor.', 403);
        }

        $deliveryTimestamp = isset($payload['deliveryTimestamp'])
            ? Carbon::parse($payload['deliveryTimestamp'])
            : now();
        $photoPath = ! empty($payload['photoDataUrl']) ? $this->storeImageFromDataUrl($payload['photoDataUrl'], 'photos', 'delivery') : null;
        $signaturePath = ! empty($payload['signatureText']) ? $this->storeSignatureSvg($payload['signatureText']) : null;
        $status = $payload['status'] ?? 'delivered';
        $delivered = str_contains(strtolower($status), 'deliver') || str_contains(strtolower($status), 'entreg');

        $id = DB::table('evidencias')->insertGetId([
            'asignacion_id' => $assignment->id ?? null,
            'package_id' => $shipment,
            'driver_id' => $driverId ?: null,
            'route_id' => $routeId ?: null,
            'delivery_timestamp' => $deliveryTimestamp,
            'recipient_name' => $payload['recipientName'],
            'signature_path' => $signaturePath,
            'photo_path' => $photoPath,
            'gps_latitude' => $payload['gpsLatitude'] ?? null,
            'gps_longitude' => $payload['gpsLongitude'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'status' => $status,
            'url_imagen' => $photoPath,
            'firma' => $payload['signatureText'] ?? null,
            'fecha' => $deliveryTimestamp,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packageUpdate = [
            'attempts' => DB::raw('COALESCE(attempts, 0) + 1'),
            'updated_at' => now(),
        ];

        if ($delivered) {
            $packageUpdate['estado_id'] = LogisticsSupport::packageStatusIdFor('Entregado');
            $packageUpdate['estado'] = 'Entregado';
            $packageUpdate['status'] = 'Entregado';
            $packageUpdate['delivery_time'] = $deliveryTimestamp;
        }

        DB::table('paquetes')->where('id', $shipment)->update($packageUpdate);

        if ($assignment) {
            DB::table('asignaciones')->where('id', $assignment->id)->update([
                'status' => $delivered ? 'delivered' : 'evidence_recorded',
                'estado' => $delivered ? 'entregada' : 'en seguimiento',
                'updated_at' => now(),
            ]);
        }

        $trackingLocation = LogisticsSupport::pickString($shipmentRecord, [
            'recipient_city',
            'destino_ciudad',
            'recipient_address',
            'destino_referencia',
        ]) ?: 'Ultima milla';

        LogisticsSupport::recordTrackingEvent(
            $shipment,
            $delivered ? 'Entrega' : 'Evidencia',
            $delivered
                ? 'Entrega confirmada para '.$payload['recipientName'].'.'
                : 'Se registro evidencia operativa para '.$payload['recipientName'].'.',
            $trackingLocation,
            $delivered ? 'Entregado' : ($shipmentRecord->status ?? 'En ruta'),
            $payload['gpsLatitude'] ?? null,
            $payload['gpsLongitude'] ?? null,
            $deliveryTimestamp,
        );

        if ($routeId) {
            $this->syncRouteProgress($routeId);
            LogisticsPlanner::syncRouteMetrics($routeId);
        }

        $item = LogisticsSupport::evidenceBaseQueryFor($request)->where('evidencias.id', $id)->first();

        return ApiResponder::success([
            'item' => LogisticsSupport::evidencePayload($item, $request),
            'message' => $delivered ? 'Evidencia registrada y envio marcado como entregado.' : 'Evidencia registrada correctamente.',
        ], 201);
    }

    private function authorizeIndex(Request $request): ?JsonResponse
    {
        if (! in_array(LogisticsSupport::roleName(LogisticsSupport::apiUser($request)), ['admin', 'supervisor', 'dispatcher', 'driver'], true)) {
            return ApiResponder::error('No tienes permisos para consultar evidencias.', 403);
        }

        return null;
    }

    private function authorizeStore(Request $request): ?JsonResponse
    {
        if (! in_array(LogisticsSupport::roleName(LogisticsSupport::apiUser($request)), ['admin', 'supervisor', 'dispatcher', 'driver'], true)) {
            return ApiResponder::error('No tienes permisos para registrar evidencias.', 403);
        }

        return null;
    }

    private function driverIdForUser(object $user): ?int
    {
        $personaId = (int) ($user->persona_id ?? 0);

        if (! $personaId) {
            return null;
        }

        $driverId = DB::table('conductores')->where('persona_id', $personaId)->value('id');

        return $driverId ? (int) $driverId : null;
    }

    private function syncRouteProgress(int $routeId): void
    {
        $packageIds = DB::table('asignaciones')
            ->where(function ($query) use ($routeId): void {
                $query->where('route_id', $routeId)
                    ->orWhere('ruta_id', $routeId);
            })
            ->pluck('package_id')
            ->filter()
            ->unique()
            ->values();

        if ($packageIds->isEmpty()) {
            return;
        }

        $total = $packageIds->count();
        $delivered = DB::table('paquetes')
            ->whereIn('id', $packageIds)
            ->where(function ($query): void {
                $query->where('estado', 'Entregado')
                    ->orWhere('status', 'Entregado')
                    ->orWhere('status', 'delivered');
            })
            ->count();

        $route = DB::table('rutas')->where('id', $routeId)->first();

        if (! $route) {
            return;
        }

        if ($delivered >= $total) {
            DB::table('rutas')->where('id', $routeId)->update([
                'status' => 'Completada',
                'estado' => 'Completada',
                'end_time' => $route->end_time ?: now(),
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('rutas')->where('id', $routeId)->update([
            'status' => in_array($route->status, ['Completada', 'Cancelada'], true) ? $route->status : 'En ejecucion',
            'estado' => in_array($route->estado, ['Completada', 'Cancelada'], true) ? $route->estado : 'En ejecucion',
            'start_time' => $route->start_time ?: now(),
            'updated_at' => now(),
        ]);
    }

    private function storeImageFromDataUrl(string $dataUrl, string $folder, string $prefix): string
    {
        if (! preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,(.+)$/i', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                'photoDataUrl' => 'El formato de la imagen no es valido.',
            ]);
        }

        $extension = strtolower($matches[1] === 'jpeg' ? 'jpg' : $matches[1]);
        $binary = base64_decode($matches[2], true);

        if ($binary === false) {
            throw ValidationException::withMessages([
                'photoDataUrl' => 'No fue posible decodificar la imagen enviada.',
            ]);
        }

        $relativeDirectory = '/uploads/evidences/'.$folder;
        $absoluteDirectory = public_path($relativeDirectory);
        File::ensureDirectoryExists($absoluteDirectory);

        $fileName = $prefix.'-'.Str::uuid().'.'.$extension;
        file_put_contents($absoluteDirectory.DIRECTORY_SEPARATOR.$fileName, $binary);

        return $relativeDirectory.'/'.$fileName;
    }

    private function storeSignatureSvg(string $signatureText): string
    {
        $relativeDirectory = '/uploads/evidences/signatures';
        $absoluteDirectory = public_path($relativeDirectory);
        File::ensureDirectoryExists($absoluteDirectory);

        $fileName = 'signature-'.Str::uuid().'.svg';
        $escaped = htmlspecialchars($signatureText, ENT_QUOTES, 'UTF-8');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="720" height="180" viewBox="0 0 720 180">
  <rect width="720" height="180" fill="#fffef8" />
  <text x="36" y="108" font-size="54" fill="#173f35" font-family="Segoe Script, Brush Script MT, cursive">{$escaped}</text>
    <text x="36" y="148" font-size="16" fill="#4c5f5a" font-family="Arial, sans-serif">Firma digital registrada en GESTIONPAQ</text>
</svg>
SVG;

        file_put_contents($absoluteDirectory.DIRECTORY_SEPARATOR.$fileName, $svg);

        return $relativeDirectory.'/'.$fileName;
    }
}