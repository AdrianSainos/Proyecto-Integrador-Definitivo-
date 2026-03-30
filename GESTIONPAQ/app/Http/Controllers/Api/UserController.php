<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Support\LogisticsSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $items = $this->users()->get()->map(fn ($item) => $this->mapUser($item))->values();

        return ApiResponder::success($items->all());
    }

    public function show(int $user): JsonResponse
    {
        $item = $this->users()->where('usuarios.id', $user)->first();

        return $item
            ? ApiResponder::success($this->mapUser($item))
            : ApiResponder::error('Usuario no encontrado.', 404);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatedPayload($request, null, true);

        $id = DB::transaction(function () use ($payload): int {
            $prepared = $this->preparePayload($payload);
            $this->ensureUniqueCredentials($prepared);

            $id = (int) DB::table('usuarios')->insertGetId($this->userValues($prepared, true));
            $this->syncProfiles($id, $prepared);

            return $id;
        });

        $item = $this->users()->where('usuarios.id', $id)->first();

        return ApiResponder::success([
            'item' => $item ? $this->mapUser($item) : null,
            'message' => 'Usuario guardado correctamente.',
        ], 201);
    }

    public function update(Request $request, int $user): JsonResponse
    {
        if (! DB::table('usuarios')->where('id', $user)->exists()) {
            return ApiResponder::error('Usuario no encontrado.', 404);
        }

        $payload = $this->validatedPayload($request, $user, false);

        DB::transaction(function () use ($payload, $user): void {
            $prepared = $this->preparePayload($payload);
            $this->ensureUniqueCredentials($prepared, $user);

            DB::table('usuarios')->where('id', $user)->update($this->userValues($prepared, false));
            $this->syncProfiles($user, $prepared);
        });

        $item = $this->users()->where('usuarios.id', $user)->first();

        return ApiResponder::success([
            'item' => $item ? $this->mapUser($item) : null,
            'message' => 'Usuario actualizado correctamente.',
        ]);
    }

    public function destroy(int $user): JsonResponse
    {
        DB::transaction(function () use ($user): void {
            DB::table('personas')->where('usuario_id', $user)->update(['usuario_id' => null, 'updated_at' => now()]);
            DB::table('usuarios')->where('id', $user)->delete();
        });

        return response()->json(null, 204);
    }

    private function users()
    {
        $select = [
            'usuarios.id',
            'usuarios.email',
            'usuarios.password',
            'usuarios.rol_id',
            'usuarios.activo',
            'roles.nombre as role_name',
            'personas.id as person_id',
            'personas.nombre',
            'personas.apellido_paterno',
            'personas.apellido_materno',
            'personas.nombres',
            'personas.apellidos',
            'personas.telefono',
            'personas.documento',
            'personas.email as person_email',
            'conductores.id as driver_id',
            'conductores.status as driver_status',
        ];

        if (LogisticsSupport::supportsUsername()) {
            $select[] = 'usuarios.username';
        }

        if (LogisticsSupport::supportsPersonnelSchedule()) {
            $select = array_merge($select, [
                'personas.employee_code',
                'personas.job_title',
                'personas.schedule_label',
                'personas.work_days',
                'personas.shift_start',
                'personas.shift_end',
            ]);
        }

        return DB::table('usuarios')
            ->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')
            ->leftJoin('personas', 'personas.usuario_id', '=', 'usuarios.id')
            ->leftJoin('conductores', 'conductores.persona_id', '=', 'personas.id')
            ->select($select);
    }

    private function mapUser(object $item): array
    {
        $payload = LogisticsSupport::userPayload($item);

        return array_merge($payload, [
            'phone' => LogisticsSupport::pickString($item, ['telefono']),
            'document' => LogisticsSupport::pickString($item, ['documento']),
            'firstName' => LogisticsSupport::pickString($item, ['nombre', 'nombres']),
            'lastName' => LogisticsSupport::pickString($item, ['apellido_paterno']),
            'secondLastName' => LogisticsSupport::pickString($item, ['apellido_materno']),
            'driverId' => LogisticsSupport::pickInt($item, ['driver_id']),
            'driverStatus' => LogisticsSupport::pickString($item, ['driver_status']) ?: 'Disponible',
            'isDriver' => (bool) LogisticsSupport::pickInt($item, ['driver_id']),
        ]);
    }

    private function validatedPayload(Request $request, ?int $userId, bool $creating): array
    {
        return $request->validate([
            'username' => ['nullable', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:100'],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:6'],
            'role' => ['required', 'string', 'max:50'],
            'active' => ['required', 'boolean'],
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'secondLastName' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'document' => ['nullable', 'string', 'max:30'],
            'employeeCode' => ['nullable', 'string', 'max:40'],
            'jobTitle' => ['nullable', 'string', 'max:120'],
            'scheduleLabel' => ['nullable', 'string', 'max:120'],
            'workDays' => ['nullable', 'string', 'max:120'],
            'shiftStart' => ['nullable', 'date_format:H:i'],
            'shiftEnd' => ['nullable', 'date_format:H:i'],
            'createDriver' => ['nullable', 'boolean'],
            'driverStatus' => ['nullable', 'string', 'max:50'],
        ]);
    }

    private function preparePayload(array $payload): array
    {
        $role = strtolower((string) $payload['role']);
        $isDriver = $role === 'driver' || (bool) ($payload['createDriver'] ?? false);
        $defaults = $this->defaultWorkProfile($role, $isDriver);

        $payload['email'] = strtolower(trim((string) $payload['email']));
        $payload['role'] = $role;
        $payload['username'] = LogisticsSupport::normalizeUsername($payload['username'] ?: $payload['email']);
        $payload['firstName'] = trim((string) $payload['firstName']);
        $payload['lastName'] = trim((string) $payload['lastName']);
        $payload['secondLastName'] = trim((string) ($payload['secondLastName'] ?? '')) ?: null;
        $payload['phone'] = trim((string) ($payload['phone'] ?? '')) ?: null;
        $payload['document'] = trim((string) ($payload['document'] ?? '')) ?: null;
        $payload['employeeCode'] = trim((string) ($payload['employeeCode'] ?? '')) ?: null;
        $payload['jobTitle'] = trim((string) ($payload['jobTitle'] ?? '')) ?: $defaults['jobTitle'];
        $payload['scheduleLabel'] = trim((string) ($payload['scheduleLabel'] ?? '')) ?: $defaults['scheduleLabel'];
        $payload['workDays'] = trim((string) ($payload['workDays'] ?? '')) ?: $defaults['workDays'];
        $payload['shiftStart'] = $payload['shiftStart'] ?? $defaults['shiftStart'];
        $payload['shiftEnd'] = $payload['shiftEnd'] ?? $defaults['shiftEnd'];
        $payload['createDriver'] = $isDriver;
        $payload['driverStatus'] = trim((string) ($payload['driverStatus'] ?? '')) ?: 'Disponible';

        return $payload;
    }

    private function ensureUniqueCredentials(array $payload, ?int $userId = null): void
    {
        $emailTaken = DB::table('usuarios')
            ->where('email', $payload['email'])
            ->when($userId, fn ($query) => $query->where('id', '!=', $userId))
            ->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages([
                'email' => 'Ese correo ya esta registrado.',
            ]);
        }

        if (LogisticsSupport::supportsUsername()) {
            $usernameTaken = DB::table('usuarios')
                ->where('username', $payload['username'])
                ->when($userId, fn ($query) => $query->where('id', '!=', $userId))
                ->exists();

            if ($usernameTaken) {
                throw ValidationException::withMessages([
                    'username' => 'Ese nombre de usuario ya esta registrado.',
                ]);
            }
        }
    }

    private function userValues(array $payload, bool $creating): array
    {
        $values = [
            'email' => $payload['email'],
            'rol_id' => LogisticsSupport::roleIdFor($payload['role']),
            'activo' => $payload['active'] ? 1 : 0,
        ];

        if (LogisticsSupport::supportsUsername()) {
            $values['username'] = $payload['username'];
        }

        if ($creating || ! empty($payload['password'])) {
            $values['password'] = Hash::make((string) $payload['password']);
        }

        if ($creating) {
            $values['api_token'] = null;
            $values['remember_token'] = null;
            $values['last_login_at'] = null;
        }

        return $values;
    }

    private function syncProfiles(int $userId, array $payload): void
    {
        $personId = $this->upsertPerson($userId, $payload);
        $this->syncDriverProfile($personId, $payload);
    }

    private function upsertPerson(int $userId, array $payload): int
    {
        $person = DB::table('personas')->where('usuario_id', $userId)->first();

        if (! $person) {
            $person = DB::table('personas')
                ->where('email', $payload['email'])
                ->where(function ($query) use ($userId): void {
                    $query->whereNull('usuario_id')->orWhere('usuario_id', $userId);
                })
                ->first();
        }

        $lastNames = trim(implode(' ', array_filter([$payload['lastName'], $payload['secondLastName']])));
        $values = [
            'usuario_id' => $userId,
            'nombre' => $payload['firstName'],
            'apellido_paterno' => $payload['lastName'],
            'apellido_materno' => $payload['secondLastName'],
            'nombres' => $payload['firstName'],
            'apellidos' => $lastNames,
            'telefono' => $payload['phone'],
            'documento' => $payload['document'],
            'email' => $payload['email'],
            'activo' => $payload['active'] ? 1 : 0,
            'updated_at' => now(),
        ];

        if (LogisticsSupport::supportsPersonnelSchedule()) {
            $values = array_merge($values, [
                'employee_code' => $payload['employeeCode'] ?: $this->defaultEmployeeCode($userId, $payload['role']),
                'job_title' => $payload['jobTitle'],
                'schedule_label' => $payload['scheduleLabel'],
                'work_days' => $payload['workDays'],
                'shift_start' => $payload['shiftStart'] ? $payload['shiftStart'].':00' : null,
                'shift_end' => $payload['shiftEnd'] ? $payload['shiftEnd'].':00' : null,
            ]);
        }

        if ($person) {
            DB::table('personas')->where('id', $person->id)->update($values);

            return (int) $person->id;
        }

        $values['created_at'] = now();

        return (int) DB::table('personas')->insertGetId($values);
    }

    private function syncDriverProfile(int $personId, array $payload): void
    {
        $driver = DB::table('conductores')->where('persona_id', $personId)->first();

        if (! $payload['createDriver'] && ! $driver) {
            return;
        }

        $status = $payload['driverStatus'] ?: ($driver->status ?? 'Disponible');
        $statusId = DB::table('estado_conductor')->where('nombre', $status)->value('id')
            ?: DB::table('estado_conductor')->where('nombre', 'Disponible')->value('id')
            ?: DB::table('estado_conductor')->orderBy('id')->value('id');

        $values = [
            'persona_id' => $personId,
            'name' => trim($payload['firstName'].' '.$payload['lastName']),
            'email' => $payload['email'],
            'phone' => $payload['phone'],
            'status' => $status,
            'estado_id' => $statusId,
            'activo' => $payload['active'] ? 1 : 0,
            'updated_at' => now(),
        ];

        if ($driver) {
            DB::table('conductores')->where('id', $driver->id)->update($values);
            $driverId = (int) $driver->id;
        } else {
            $values['created_at'] = now();
            $driverId = (int) DB::table('conductores')->insertGetId($values);
        }

        $this->ensureDriverShift($driverId, $payload, $status);
    }

    private function ensureDriverShift(int $driverId, array $payload, string $status): void
    {
        if (! $payload['shiftStart'] || ! $payload['shiftEnd']) {
            return;
        }

        $shiftDate = now()->toDateString();
        $exists = DB::table('turnos_conductor')
            ->where('driver_id', $driverId)
            ->where('shift_date', $shiftDate)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('turnos_conductor')->insert([
            'conductor_id' => $driverId,
            'driver_id' => $driverId,
            'shift_date' => $shiftDate,
            'start_time' => $payload['shiftStart'].':00',
            'end_time' => $payload['shiftEnd'].':00',
            'total_deliveries' => 0,
            'successful_deliveries' => 0,
            'failed_deliveries' => 0,
            'distance_km' => 0,
            'status' => strtolower($status) === 'en ruta' ? 'in_progress' : 'scheduled',
            'inicio_turno' => now()->setTime((int) substr($payload['shiftStart'], 0, 2), (int) substr($payload['shiftStart'], 3, 2)),
            'fin_turno' => null,
            'estado' => strtolower($status) === 'fuera de turno' ? 'cerrado' : 'programado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function defaultWorkProfile(string $role, bool $isDriver): array
    {
        if ($isDriver) {
            return [
                'jobTitle' => 'Conductor de reparto',
                'scheduleLabel' => 'Primera salida',
                'workDays' => 'Lun-Sab',
                'shiftStart' => '07:30',
                'shiftEnd' => '18:00',
            ];
        }

        return match ($role) {
            'admin' => ['jobTitle' => 'Administrador de plataforma', 'scheduleLabel' => 'Jornada administrativa', 'workDays' => 'Lun-Vie', 'shiftStart' => '08:00', 'shiftEnd' => '17:00'],
            'supervisor' => ['jobTitle' => 'Supervisor logistico', 'scheduleLabel' => 'Supervision regional', 'workDays' => 'Lun-Sab', 'shiftStart' => '09:00', 'shiftEnd' => '18:00'],
            'dispatcher' => ['jobTitle' => 'Despachador operativo', 'scheduleLabel' => 'Despacho AM', 'workDays' => 'Lun-Sab', 'shiftStart' => '06:00', 'shiftEnd' => '15:00'],
            'customer' => ['jobTitle' => 'Contacto cliente', 'scheduleLabel' => 'Portal cliente', 'workDays' => 'Lun-Dom', 'shiftStart' => '00:00', 'shiftEnd' => '23:59'],
            default => ['jobTitle' => 'Operador logistico', 'scheduleLabel' => 'Mesa operativa', 'workDays' => 'Lun-Sab', 'shiftStart' => '07:00', 'shiftEnd' => '16:00'],
        };
    }

    private function defaultEmployeeCode(int $userId, string $role): string
    {
        $prefix = match ($role) {
            'admin' => 'ADM',
            'supervisor' => 'SUP',
            'dispatcher' => 'DSP',
            'driver' => 'DRV',
            'customer' => 'CLI',
            default => 'OPR',
        };

        return sprintf('%s-%04d', $prefix, $userId);
    }
}