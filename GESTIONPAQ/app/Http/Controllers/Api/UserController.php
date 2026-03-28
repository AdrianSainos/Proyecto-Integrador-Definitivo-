<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $items = $this->users()->get()->map(fn ($item) => [
            'id' => (int) $item->id,
            'email' => $item->email,
            'role' => $item->role_name ?: 'operator',
            'active' => (bool) $item->activo,
            'name' => LogisticsSupport::userDisplayName($item),
        ])->values();

        return ApiResponder::success($items->all());
    }

    public function show(int $user): JsonResponse
    {
        $item = $this->users()->where('usuarios.id', $user)->first();

        return $item
            ? ApiResponder::success([
                'id' => (int) $item->id,
                'email' => $item->email,
                'role' => $item->role_name ?: 'operator',
                'active' => (bool) $item->activo,
                'name' => LogisticsSupport::userDisplayName($item),
            ])
            : ApiResponder::error('Usuario no encontrado.', 404);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string'],
            'active' => ['required', 'boolean'],
        ]);

        $id = DB::table('usuarios')->insertGetId([
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
            'rol_id' => LogisticsSupport::roleIdFor($payload['role']),
            'activo' => $payload['active'] ? 1 : 0,
        ]);

        return ApiResponder::success([
            'item' => $this->show($id)->getData(true),
            'message' => 'Usuario guardado correctamente.',
        ], 201);
    }

    public function update(Request $request, int $user): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['sometimes', 'email'],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['sometimes', 'string'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $update = [];
        if (isset($payload['email'])) {
            $update['email'] = $payload['email'];
        }
        if (! empty($payload['password'])) {
            $update['password'] = Hash::make($payload['password']);
        }
        if (isset($payload['role'])) {
            $update['rol_id'] = LogisticsSupport::roleIdFor($payload['role']);
        }
        if (array_key_exists('active', $payload)) {
            $update['activo'] = $payload['active'] ? 1 : 0;
        }

        DB::table('usuarios')->where('id', $user)->update($update);

        return ApiResponder::success([
            'item' => $this->show($user)->getData(true),
            'message' => 'Usuario actualizado correctamente.',
        ]);
    }

    public function destroy(int $user): JsonResponse
    {
        DB::table('usuarios')->where('id', $user)->delete();

        return response()->json(null, 204);
    }

    private function users()
    {
        return DB::table('usuarios')
            ->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')
            ->leftJoin('personas', 'personas.usuario_id', '=', 'usuarios.id')
            ->select([
                'usuarios.*',
                'roles.nombre as role_name',
                'personas.nombre',
                'personas.apellido_paterno',
                'personas.nombres',
                'personas.apellidos',
            ]);
    }
}