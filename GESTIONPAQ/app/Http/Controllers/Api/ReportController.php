<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsAnalytics;
use App\Support\LogisticsSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeRole($request);

        return ApiResponder::success($this->reportPayload($request));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorizeRole($request);

        $payload = $this->reportPayload($request);
        $fileName = 'reporte-'.$payload['range']['key'].'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($payload): void {
            $stream = fopen('php://output', 'wb');

            fputcsv($stream, ['Indicador', 'Valor', 'Variacion']);

            foreach ($payload['rows'] as $row) {
                fputcsv($stream, [$row['metric'], $row['value'], $row['variation']]);
            }

            fclose($stream);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request)
    {
        $this->authorizeRole($request);

        $payload = $this->reportPayload($request);
        $fileName = 'reporte-'.$payload['range']['key'].'-'.now()->format('Ymd-His').'.pdf';

        return Pdf::loadHTML($this->pdfHtml($payload))
            ->setPaper('a4', 'portrait')
            ->download($fileName);
    }

    private function authorizeRole(Request $request): void
    {
        $role = LogisticsSupport::roleName(LogisticsSupport::apiUser($request));

        abort_unless(in_array($role, ['admin', 'supervisor'], true), 403, 'No tienes permisos para consultar reportes.');
    }

    private function reportPayload(Request $request): array
    {
        $window = LogisticsAnalytics::reportWindow($request->query('range'));
        $shipments = LogisticsSupport::shipmentBaseQueryFor($request)->get()->map(fn ($item) => LogisticsSupport::shipmentPayload($item));
        $routes = LogisticsSupport::routeBaseQueryFor($request)->get()->map(fn ($item) => LogisticsSupport::routePayload($item));
        $events = LogisticsAnalytics::trackingEventsForShipments($shipments);

        return LogisticsAnalytics::buildReport($shipments, $routes, $events, $window);
    }

    private function pdfHtml(array $report): string
    {
        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $rows = '';

        foreach ($report['rows'] as $row) {
            $rows .= '<tr>'
                .'<td>'.$escape($row['metric'] ?? '').'</td>'
                .'<td>'.$escape($row['value'] ?? '').'</td>'
                .'<td>'.$escape($row['variation'] ?? '').'</td>'
                .'</tr>';
        }

        $cards = '';

        foreach (array_values($report['cards']) as $index => $card) {
            if ($index % 2 === 0) {
                $cards .= '<tr>';
            }

            $cards .= '<td class="card">'
                .'<div class="card-label">'.$escape($card['title'] ?? '').'</div>'
                .'<div class="card-value">'.$escape($card['value'] ?? '').'</div>'
                .'<div class="card-detail">'.$escape($card['detail'] ?? '').'</div>'
                .'</td>';

            if ($index % 2 === 1) {
                $cards .= '</tr>';
            }
        }

        if ((count($report['cards']) % 2) === 1) {
            $cards .= '<td class="card"></td></tr>';
        }

        return '<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte GESTIONPAQ</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; color: #1d2433; margin: 28px; font-size: 12px; }
    .header { margin-bottom: 24px; padding: 18px 20px; border-radius: 16px; background: #0f7b6c; color: #ffffff; }
    .eyebrow { text-transform: uppercase; letter-spacing: 0.16em; font-size: 10px; opacity: 0.75; }
    h1, h2, p { margin: 0; }
    .subtitle { margin-top: 8px; opacity: 0.88; }
    .meta { margin-top: 10px; font-size: 11px; opacity: 0.9; }
    .grid { width: 100%; border-collapse: separate; border-spacing: 10px; margin: 0 -10px 18px; }
    .card { width: 50%; vertical-align: top; padding: 14px; border-radius: 14px; background: #f6faf9; border: 1px solid #d9ebe8; }
    .card-label { color: #5d6880; font-size: 10px; text-transform: uppercase; letter-spacing: 0.12em; }
    .card-value { font-size: 20px; font-weight: bold; margin-top: 8px; color: #0b5d52; }
    .card-detail { margin-top: 6px; color: #536079; font-size: 11px; }
    .section-title { margin: 18px 0 10px; font-size: 15px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { padding: 10px 12px; border-bottom: 1px solid #dbe5ea; text-align: left; }
    th { background: #f3f6f8; color: #536079; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; }
  </style>
</head>
<body>
  <section class="header">
    <div class="eyebrow">GESTIONPAQ</div>
    <h1>Reporte operativo</h1>
    <p class="subtitle">'
            .$escape($report['range']['label'] ?? '')
            .' - '.$escape($report['range']['from'] ?? '')
            .' a '.$escape($report['range']['to'] ?? '')
            .'</p>
    <p class="meta">Generado: '.$escape(now()->format('d/m/Y H:i')).'</p>
  </section>
  <table class="grid">'.$cards.'</table>
  <h2 class="section-title">Indicadores del periodo</h2>
  <table>
    <thead>
      <tr>
        <th>Indicador</th>
        <th>Valor</th>
        <th>Variacion</th>
      </tr>
    </thead>
    <tbody>'.$rows.'</tbody>
  </table>
</body>
</html>';
    }
}
