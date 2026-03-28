<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = DB::table('usuarios')
            ->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')
            ->leftJoin('personas', 'personas.usuario_id', '=', 'usuarios.id')
            ->where('usuarios.email', $credentials['email'])
            ->select([
                'usuarios.*',
                'roles.nombre as role_name',
                'personas.nombre',
                'personas.apellido_paterno',
                'personas.nombres',
                'personas.apellidos',
            ])
            ->first();

        if (! $user || ! $this->passwordMatches($credentials['password'], (string) $user->password)) {
            return ApiResponder::error('Credenciales invalidas.', 401);
        }

        if (! $user->activo) {
            return ApiResponder::error('El usuario no esta activo.', 403);
        }

        $token = LogisticsSupport::generateToken();

        DB::table('usuarios')
            ->where('id', $user->id)
            ->update([
                'api_token' => $token,
                'last_login_at' => now(),
            ]);

        $user->api_token = $token;

        return ApiResponder::success([
            'token' => $token,
            'user' => LogisticsSupport::userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponder::success([
            'user' => LogisticsSupport::userPayload(LogisticsSupport::apiUser($request)),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = LogisticsSupport::apiUser($request);

        DB::table('usuarios')
            ->where('id', $user->id)
            ->update(['api_token' => null]);

        return ApiResponder::success(['message' => 'Sesion cerrada correctamente.']);
    }

    private function passwordMatches(string $plain, string $stored): bool
    {
        return Hash::check($plain, $stored) || hash_equals($stored, $plain);
    }
}