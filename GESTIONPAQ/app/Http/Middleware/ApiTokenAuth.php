<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return new JsonResponse(['message' => 'No autenticado.'], 401);
        }

        $user = DB::table('usuarios')
            ->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')
            ->leftJoin('personas', 'personas.usuario_id', '=', 'usuarios.id')
            ->where('usuarios.api_token', $token)
            ->where('usuarios.activo', 1)
            ->select([
                'usuarios.id',
                'usuarios.email',
                'usuarios.rol_id',
                'usuarios.activo',
                'roles.nombre as role_name',
                'personas.id as persona_id',
                'personas.nombre',
                'personas.apellido_paterno',
                'personas.nombres',
                'personas.apellidos',
            ])
            ->first();

        if (! $user) {
            return new JsonResponse(['message' => 'Sesion expirada.'], 401);
        }

        $request->attributes->set('apiUser', $user);

        return $next($request);
    }
}