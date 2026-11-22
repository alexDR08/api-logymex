<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    private const ROLES_PERMITIDOS = [
        'director_general',
        'jefe_logistica',
        'ejecutivo_comercial',
        'operador',
    ];

    public function listar(Request $request): JsonResponse
    {
        if (! $this->puedeConsultarUsuarios($request->user())) {
            return $this->respuestaNoAutorizada();
        }

        $usuarios = User::query()
            ->with('roles:id,name')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'mensaje' => 'Usuarios consultados correctamente.',
            'datos' => $usuarios,
        ]);
    }

    public function registrar(Request $request): JsonResponse
    {
        if (! $this->esDirectorGeneral($request->user())) {
            return $this->respuestaNoAutorizada('Solo el director general puede registrar usuarios.');
        }

        $datos = $request->validate([
<<<<<<< HEAD
            'name' => ['required', 'string', 'max:255'],
            'rol' => ['required', Rule::in(self::ROLES_PERMITIDOS)],
            'email' => [
                Rule::requiredIf(fn () => $request->input('rol') !== 'operador'),
                'nullable', 'email', 'max:255', 'unique:users,email',
            ],
            'telefono' => [
                Rule::requiredIf(fn () => $request->input('rol') === 'operador'),
                'nullable', 'string', 'max:20', 'unique:users,telefono',
            ],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
=======
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'rol' => ['required', Rule::in(self::ROLES_PERMITIDOS)],
            'permisos' => ['nullable', 'array'],
        ]);

        $usuario = User::create([
            'nombre' => $datos['nombre'],
            'apellidos' => $datos['apellidos'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'email' => $datos['email'] ?? null,
>>>>>>> 6c520b196f7a6fe93dc8c5965e5abb67d6cdea03
            'password' => Hash::make($datos['password']),
            'rol' => $datos['rol'],
            'permisos' => $datos['permisos'] ?? null,
        ]);

        Role::findOrCreate($datos['rol']);
        $usuario->assignRole($datos['rol']);
        $usuario->load('roles:id,name');

        return response()->json([
            'mensaje' => 'Usuario registrado correctamente.',
            'datos' => $usuario,
        ], 201);
    }

    public function mostrar(Request $request, User $usuario): JsonResponse
    {
        if (! $this->puedeConsultarUsuarios($request->user())) {
            return $this->respuestaNoAutorizada();
        }

        return response()->json([
            'mensaje' => 'Usuario consultado correctamente.',
            'datos' => $usuario->load('roles:id,name'),
        ]);
    }

    public function actualizar(Request $request, User $usuario): JsonResponse
    {
        if (! $this->puedeConsultarUsuarios($request->user())) {
            return $this->respuestaNoAutorizada();
        }

        $reglas = [
<<<<<<< HEAD
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('users', 'telefono')->ignore($usuario->id)],
=======
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'apellidos' => ['sometimes', 'nullable', 'string', 'max:255'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
>>>>>>> 6c520b196f7a6fe93dc8c5965e5abb67d6cdea03
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'permisos' => ['sometimes', 'nullable', 'array'],
        ];

        if ($this->esDirectorGeneral($request->user())) {
            $reglas['rol'] = ['sometimes', 'required', Rule::in(self::ROLES_PERMITIDOS)];
        }

        $datos = $request->validate($reglas);

        $usuario->fill([
<<<<<<< HEAD
            'name' => $datos['name'] ?? $usuario->name,
            'email' => array_key_exists('email', $datos) ? $datos['email'] : $usuario->email,
            'telefono' => array_key_exists('telefono', $datos) ? $datos['telefono'] : $usuario->telefono,
=======
            'nombre' => $datos['nombre'] ?? $usuario->nombre,
            'apellidos' => array_key_exists('apellidos', $datos) ? $datos['apellidos'] : $usuario->apellidos,
            'telefono' => array_key_exists('telefono', $datos) ? $datos['telefono'] : $usuario->telefono,
            'email' => array_key_exists('email', $datos) ? $datos['email'] : $usuario->email,
            'permisos' => array_key_exists('permisos', $datos) ? $datos['permisos'] : $usuario->permisos,
>>>>>>> 6c520b196f7a6fe93dc8c5965e5abb67d6cdea03
        ]);

        if (! empty($datos['password'])) {
            $usuario->password = Hash::make($datos['password']);
        }

        $usuario->save();

        if ($this->esDirectorGeneral($request->user()) && isset($datos['rol'])) {
            $usuario->rol = $datos['rol'];
            $usuario->save();
            $usuario->syncRoles([$datos['rol']]);
        }

        return response()->json([
            'mensaje' => 'Usuario actualizado correctamente.',
            'datos' => $usuario->load('roles:id,name'),
        ]);
    }

    public function eliminar(Request $request, User $usuario): JsonResponse
    {
        if (! $this->esDirectorGeneral($request->user())) {
            return $this->respuestaNoAutorizada('Solo el director general puede eliminar usuarios.');
        }

        if ($request->user()->is($usuario)) {
            return response()->json([
                'mensaje' => 'No puedes eliminar tu propio usuario autenticado.',
            ], 422);
        }

        $usuario->delete();

        return response()->json([
            'mensaje' => 'Usuario eliminado correctamente.',
        ]);
    }

    private function puedeConsultarUsuarios(User $usuario): bool
    {
        return $usuario->hasAnyRole(['director_general', 'jefe_logistica']);
    }

    private function esDirectorGeneral(User $usuario): bool
    {
        return $usuario->hasRole('director_general');
    }

    private function respuestaNoAutorizada(string $mensaje = 'No tienes permisos para realizar esta accion.'): JsonResponse
    {
        return response()->json([
            'mensaje' => $mensaje,
        ], 403);
    }
}
