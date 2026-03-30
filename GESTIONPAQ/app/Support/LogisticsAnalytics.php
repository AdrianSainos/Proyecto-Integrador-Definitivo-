<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LogisticsAnalytics
{
    public static function dashboardWindow(?string $range): array
    {
        $key = in_array($range, ['today', 'week'], true) ? $range : 'week';
        $end = now();

        if ($key === 'today') {
            return [
                'key' => 'today',
                'label' => 'Hoy',
                'start' => $end->copy()->startOfDay(),
                'end' => $end->copy()->endOfDay(),
            ];
        }

        return [
            'key' => 'week',
            'label' => 'Ultimos 7 dias',
            'start' => $end->copy()->subDays(6)->startOfDay(),
            'end' => $end->copy()->endOfDay(),
        ];
    }

    public static function reportWindow(?string $range): array
    {
        $key = in_array($range, ['today', 'week', 'month'], true) ? $range : 'today';
        $end = now()->endOfDay();

        return match ($key) {
            'week' => [
                'key' => 'week',
                'label' => 'Ultimos 7 dias',
                'start' => $end->copy()->subDays(6)->startOfDay(),
                'end' => $end->copy(),
                'previousStart' => $end->copy()->subDays(13)->startOfDay(),
                'previousEnd' => $end->copy()->subDays(7)->endOfDay(),
            ],
            'month' => [
                'key' => 'month',
                'label' => 'Ultimos 30 dias',
                'start' => $end->copy()->subDays(29)->startOfDay(),
                'end' => $end->copy(),
                'previousStart' => $end->copy()->subDays(59)->startOfDay(),
                'previousEnd' => $end->copy()->subDays(30)->endOfDay(),
            ],
            default => [
                'key' => 'today',
                'label' => 'Hoy',
                'start' => $end->copy()->startOfDay(),
                'end' => $end->copy(),
                'previousStart' => $end->copy()->subDay()->startOfDay(),
                'previousEnd' => $end->copy()->subDay()->endOfDay(),
            ],
        };
    }

    public static function trackingEventsForShipments(Collection $shipments): Collection
    {
        $shipmentIds = $shipments->pluck('id')->filter()->unique()->values();

        if ($shipmentIds->isEmpty()) {
            return collect();
        }

        return DB::table('tracking')
            ->leftJoin('estado_paquete', 'estado_paquete.id', '=', 'tracking.estado_id')
            ->where(function ($query) use ($shipmentIds): void {
                $query->whereIn('tracking.paquete_id', $shipmentIds)
                    ->orWhereIn('tracking.package_id', $shipmentIds);
            })
            ->orderBy('tracking.fecha')
            ->get([
                'tracking.id',
                'tracking.paquete_id',
                'tracking.package_id',
                'tracking.event_type',
                'tracking.description',
                'tracking.location',
                'tracking.timestamp_event',
                'tracking.fecha',
                'estado_paquete.nombre as status_name',
            ])
            ->map(fn ($event) => [
                'id' => (int) $event->id,
                'shipmentId' => (int) ($event->paquete_id ?: $event->package_id ?: 0),
                'type' => $event->event_type ?: 'Evento',
                'description' => $event->description ?: 'Actualizacion de rastreo',
                'location' => $event->location ?: 'Sin ubicacion',
                'status' => $event->status_name ?: null,
                'timestamp' => $event->timestamp_event ?: $event->fecha,
            ])
            ->values();
    }

    public static function filterByRange(Collection $items, callable $resolver, Carbon $start, Carbon $end): Collection
    {
        return $items
            ->filter(function ($item) use ($resolver, $start, $end): bool {
                $moment = $resolver($item);

                return $moment ? $moment->betweenIncluded($start, $end) : false;
            })
            ->values();
    }

    public static function shipmentMoment(array $shipment): ?Carbon
    {
        return self::parseMoment(
            $shipment['deliveryTime']
                ?? $shipment['assignedAt']
                ?? $shipment['scheduledDate']
                ?? $shipment['createdAt']
                ?? null
        );
    }

    public static function routeMoment(array $route): ?Carbon
    {
        return self::parseMoment(
            $route['endTime']
                ?? $route['startTime']
                ?? $route['scheduledDate']
                ?? $route['createdAt']
                ?? null
        );
    }

    public static function eventMoment(array $event): ?Carbon
    {
        return self::parseMoment($event['timestamp'] ?? null);
    }

    public static function parseMoment(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function completionRate(Collection $shipments): float
    {
        if ($shipments->isEmpty()) {
            return 0;
        }

        return round(($shipments->filter(fn ($shipment) => self::isDeliveredShipment($shipment))->count() / $shipments->count()) * 100, 1);
    }

    public static function onTimeRate(Collection $shipments): float
    {
        $delivered = $shipments->filter(fn ($shipment) => self::isDeliveredShipment($shipment))->values();

        if ($delivered->isEmpty()) {
            return 0;
        }

        $eligible = $delivered->filter(function ($shipment): bool {
            return self::parseMoment($shipment['deliveryTime'] ?? null) !== null
                && self::parseMoment($shipment['promisedDate'] ?? $shipment['scheduledDate'] ?? null) !== null;
        });

        if ($eligible->isEmpty()) {
            return self::completionRate($shipments);
        }

        $onTime = $eligible->filter(function ($shipment): bool {
            $delivery = self::parseMoment($shipment['deliveryTime'] ?? null);
            $deadline = self::parseMoment($shipment['promisedDate'] ?? $shipment['scheduledDate'] ?? null);

            return $delivery !== null && $deadline !== null && $delivery->lte($deadline->copy()->endOfDay());
        });

        return round(($onTime->count() / $eligible->count()) * 100, 1);
    }

    public static function capacityUseRate(Collection $routes, Collection $vehicles): float
    {
        if ($vehicles->isEmpty()) {
            return 0;
        }

        $usedVehicles = $routes
            ->filter(fn ($route) => self::isActiveRoute($route))
            ->pluck('vehicleId')
            ->filter()
            ->unique()
            ->count();

        return round(($usedVehicles / $vehicles->count()) * 100, 1);
    }

    public static function operationalEvolution(Collection $shipments, array $window): array
    {
        $labels = [];
        $data = [];

        if ($window['key'] === 'today') {
            for ($offset = 7; $offset >= 0; $offset--) {
                $bucketStart = $window['end']->copy()->subHours($offset)->startOfHour();
                $bucketEnd = $bucketStart->copy()->endOfHour();

                $labels[] = $bucketStart->format('H:i');
                $data[] = self::filterByRange($shipments, [self::class, 'shipmentMoment'], $bucketStart, $bucketEnd)->count();
            }

            return ['labels' => $labels, 'data' => $data];
        }

        for ($offset = 6; $offset >= 0; $offset--) {
            $bucketStart = $window['end']->copy()->subDays($offset)->startOfDay();
            $bucketEnd = $bucketStart->copy()->endOfDay();

            $labels[] = $bucketStart->format('d/m');
            $data[] = self::filterByRange($shipments, [self::class, 'shipmentMoment'], $bucketStart, $bucketEnd)->count();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public static function deliveriesByHour(Collection $shipments, Collection $events): array
    {
        $labels = ['08', '10', '12', '14', '16', '18', '20'];
        $data = array_fill(0, count($labels), 0);

        $moments = $events
            ->filter(fn ($event) => self::isDeliveredEvent($event))
            ->map(fn ($event) => self::eventMoment($event))
            ->filter();

        if ($moments->isEmpty()) {
            $moments = $shipments
                ->filter(fn ($shipment) => self::isDeliveredShipment($shipment))
                ->map(fn ($shipment) => self::parseMoment($shipment['deliveryTime'] ?? null))
                ->filter();
        }

        $moments->each(function (Carbon $moment) use (&$data): void {
            if ($moment->hour < 8 || $moment->hour > 21) {
                return;
            }

            $bucket = min(6, intdiv(max(0, $moment->hour - 8), 2));
            $data[$bucket]++;
        });

        return ['labels' => $labels, 'data' => $data];
    }

    public static function buildReport(Collection $shipments, Collection $routes, Collection $events, array $window): array
    {
        $currentShipments = self::filterByRange($shipments, [self::class, 'shipmentMoment'], $window['start'], $window['end']);
        $previousShipments = self::filterByRange($shipments, [self::class, 'shipmentMoment'], $window['previousStart'], $window['previousEnd']);
        $currentRoutes = self::filterByRange($routes, [self::class, 'routeMoment'], $window['start'], $window['end']);
        $previousRoutes = self::filterByRange($routes, [self::class, 'routeMoment'], $window['previousStart'], $window['previousEnd']);
        $currentEvents = self::filterByRange($events, [self::class, 'eventMoment'], $window['start'], $window['end']);
        $previousEvents = self::filterByRange($events, [self::class, 'eventMoment'], $window['previousStart'], $window['previousEnd']);

        $currentDelivered = $currentShipments->filter(fn ($shipment) => self::isDeliveredShipment($shipment));
        $previousDelivered = $previousShipments->filter(fn ($shipment) => self::isDeliveredShipment($shipment));
        $currentPending = $currentShipments->reject(fn ($shipment) => self::isDeliveredShipment($shipment));
        $previousPending = $previousShipments->reject(fn ($shipment) => self::isDeliveredShipment($shipment));
        $currentActiveRoutes = $currentRoutes->filter(fn ($route) => self::isActiveRoute($route));
        $previousActiveRoutes = $previousRoutes->filter(fn ($route) => self::isActiveRoute($route));
        $currentAvgTime = round((float) $currentRoutes->avg('actualTimeMinutes') ?: (float) $currentRoutes->avg('timeMinutes'));
        $previousAvgTime = round((float) $previousRoutes->avg('actualTimeMinutes') ?: (float) $previousRoutes->avg('timeMinutes'));
        $currentFuel = round((float) $currentRoutes->sum('fuelConsumedLiters'), 1);
        $previousFuel = round((float) $previousRoutes->sum('fuelConsumedLiters'), 1);
        $currentEfficiency = round((float) $currentRoutes->avg('optimizationScore'), 1);
        $previousEfficiency = round((float) $previousRoutes->avg('optimizationScore'), 1);
        $currentSla = self::onTimeRate($currentShipments);
        $previousSla = self::onTimeRate($previousShipments);
        $currentUpdates = $currentEvents->count();
        $previousUpdates = $previousEvents->count();

        return [
            'range' => [
                'key' => $window['key'],
                'label' => $window['label'],
                'from' => $window['start']->toDateString(),
                'to' => $window['end']->toDateString(),
            ],
            'cards' => [
                ['title' => 'Envios del periodo', 'value' => (string) $currentShipments->count(), 'detail' => 'Visibles en el rango actual'],
                ['title' => 'Entregas completadas', 'value' => (string) $currentDelivered->count(), 'detail' => self::formatVariation($currentDelivered->count(), $previousDelivered->count())],
                ['title' => 'Cumplimiento SLA', 'value' => self::formatPercent($currentSla), 'detail' => self::formatVariation($currentSla, $previousSla)],
                ['title' => 'Eficiencia de rutas', 'value' => $currentEfficiency ? $currentEfficiency.' pts' : '0 pts', 'detail' => self::formatVariation($currentEfficiency, $previousEfficiency)],
                ['title' => 'Combustible consumido', 'value' => $currentFuel ? $currentFuel.' L' : '0 L', 'detail' => self::formatVariation($currentFuel, $previousFuel)],
            ],
            'rows' => [
                ['metric' => 'Envios registrados', 'value' => $currentShipments->count(), 'variation' => self::formatVariation($currentShipments->count(), $previousShipments->count())],
                ['metric' => 'Entregas exitosas', 'value' => $currentDelivered->count(), 'variation' => self::formatVariation($currentDelivered->count(), $previousDelivered->count())],
                ['metric' => 'Incidencias abiertas', 'value' => $currentPending->count(), 'variation' => self::formatVariation($currentPending->count(), $previousPending->count(), true)],
                ['metric' => 'Rutas activas', 'value' => $currentActiveRoutes->count(), 'variation' => self::formatVariation($currentActiveRoutes->count(), $previousActiveRoutes->count())],
                ['metric' => 'Tiempo promedio de ruta', 'value' => $currentAvgTime ? $currentAvgTime.' min' : '0 min', 'variation' => self::formatVariation($currentAvgTime, $previousAvgTime, true)],
                ['metric' => 'Actualizaciones de rastreo', 'value' => $currentUpdates, 'variation' => self::formatVariation($currentUpdates, $previousUpdates)],
            ],
        ];
    }

    public static function formatPercent(float $value): string
    {
        return number_format($value, 1).'%';
    }

    public static function formatVariation(float|int $current, float|int $previous, bool $inverse = false): string
    {
        if ($previous == 0.0) {
            if ($current == 0.0) {
                return '0%';
            }

            return '+100%';
        }

        $delta = (($current - $previous) / abs($previous)) * 100;
        $delta = $inverse ? $delta * -1 : $delta;
        $prefix = $delta > 0 ? '+' : '';

        return $prefix.number_format($delta, 1).'%';
    }

    public static function isDeliveredShipment(array $shipment): bool
    {
        $status = strtolower((string) ($shipment['status'] ?? ''));

        return str_contains($status, 'entreg');
    }

    public static function isDeliveredEvent(array $event): bool
    {
        $haystack = strtolower(trim(($event['type'] ?? '').' '.($event['status'] ?? '').' '.($event['description'] ?? '')));

        return str_contains($haystack, 'entreg');
    }

    public static function isActiveRoute(array $route): bool
    {
        $status = strtolower((string) ($route['status'] ?? ''));

        foreach (['ejec', 'prepar', 'activ', 'planned', 'plan'] as $token) {
            if (str_contains($status, $token)) {
                return true;
            }
        }

        return false;
    }
}