<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    private array $defaults = [
        'companyName' => 'GESTIONPAQ',
        'supportEmail' => 'soporte@gestionpaq.mx',
        'supportPhone' => '555-000-4455',
        'dispatchStartTime' => '06:30',
        'defaultLeadDays' => 2,
        'maxDeliveryAttempts' => 3,
        'requirePhoto' => true,
        'requireSignature' => true,
    ];

    public function show(): JsonResponse
    {
        return ApiResponder::success($this->settingsPayload());
    }

    public function update(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'companyName' => ['required', 'string', 'max:160'],
            'supportEmail' => ['required', 'email'],
            'supportPhone' => ['required', 'string', 'max:40'],
            'dispatchStartTime' => ['required', 'date_format:H:i'],
            'defaultLeadDays' => ['required', 'integer', 'min:0'],
            'maxDeliveryAttempts' => ['required', 'integer', 'min:1'],
            'requirePhoto' => ['required', 'boolean'],
            'requireSignature' => ['required', 'boolean'],
        ]);

        foreach ($payload as $key => $value) {
            DB::table('configuracion_sistema')->updateOrInsert(
                ['clave' => $key],
                [
                    'valor' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    'tipo' => is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string'),
                    'grupo' => in_array($key, ['companyName', 'supportEmail', 'supportPhone'], true) ? 'identidad' : (in_array($key, ['dispatchStartTime', 'defaultLeadDays', 'maxDeliveryAttempts'], true) ? 'despacho' : 'evidencia'),
                    'etiqueta' => $key,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return ApiResponder::success([
            'item' => $this->settingsPayload(),
            'message' => 'Configuracion guardada correctamente.',
        ]);
    }

    private function settingsPayload(): array
    {
        $rows = DB::table('configuracion_sistema')->pluck('valor', 'clave')->all();
        $settings = array_merge($this->defaults, $rows);

        $settings['defaultLeadDays'] = (int) $settings['defaultLeadDays'];
        $settings['maxDeliveryAttempts'] = (int) $settings['maxDeliveryAttempts'];
        $settings['requirePhoto'] = in_array((string) $settings['requirePhoto'], ['1', 'true', 'on'], true);
        $settings['requireSignature'] = in_array((string) $settings['requireSignature'], ['1', 'true', 'on'], true);

        return $settings;
    }
}