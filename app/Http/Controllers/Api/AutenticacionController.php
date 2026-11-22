<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AutenticacionController extends Controller
{
    /**
     * Iniciar sesión con email o teléfono y obtener token Bearer de Sanctum.
     */
    public function iniciarSesion(Request $request): JsonResponse
    {
<<<<<<< HEAD
        $credenciales = $request->validate([
            'identificador' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $usuario = User::where('email', $credenciales['identificador'])
            ->orWhere('telefono', $credenciales['identificador'])
            ->first();
=======
        $datos = $request->validate([
            'login' => ['sometimes', 'required', 'string'],
            'email' => ['sometimes', 'nullable', 'string'],
            'telefono' => ['sometimes', 'nullable', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identificador = $datos['login'] ?? $datos['email'] ?? $datos['telefono'] ?? null;
>>>>>>> 6c520b196f7a6fe93dc8c5965e5abb67d6cdea03

        if (! $identificador) {
            throw ValidationException::withMessages([
<<<<<<< HEAD
                'identificador' => ['Las credenciales no son correctas.'],
=======
                'login' => ['Debes proporcionar un email o número de teléfono para iniciar sesión.'],
>>>>>>> 6c520b196f7a6fe93dc8c5965e5abb67d6cdea03
            ]);
        }

        $usuario = User::where('email', $identificador)
            ->orWhere('telefono', $identificador)
            ->first();

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            throw ValidationException::withMessages([
                'login' => ['Las credenciales proporcionadas no son correctas.'],
            ]);
        }

        // Revocar tokens anteriores si fuera necesario o generar nuevo token
        $token = $usuario->createToken('token-logymex')->plainTextToken;

        return response()->json([
            'mensaje' => 'Sesión iniciada correctamente.',
            'token' => $token,
            'token_type' => 'Bearer',
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'apellidos' => $usuario->apellidos,
                'telefono' => $usuario->telefono,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'permisos' => $usuario->permisos ?? [],
                'roles' => $usuario->getRoleNames(),
                'permisos_spatie' => $usuario->getAllPermissions()->pluck('name'),
            ],
        ], 200);
    }

    /**
     * Obtener los datos del usuario autenticado actual.
     */
    public function perfil(Request $request): JsonResponse
    {
        $usuario = $request->user();

        return response()->json([
            'mensaje' => 'Perfil consultado correctamente.',
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'apellidos' => $usuario->apellidos,
                'telefono' => $usuario->telefono,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'permisos' => $usuario->permisos ?? [],
                'roles' => $usuario->getRoleNames(),
                'permisos_spatie' => $usuario->getAllPermissions()->pluck('name'),
            ],
        ], 200);
    }

    /**
     * Cerrar sesión y revocar el token de acceso actual.
     */
    public function cerrarSesion(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente y token revocado.',
        ], 200);
    }
}
