<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UsuarioSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Resetear la caché de permisos al inicio
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'web';

        // Permisos del sistema
        $permisos = [
            'crear-usuarios',
            'eliminar-usuarios',
            'crear-ordenes-servicio',
            'eliminar-ordenes-servicio',
            'visualizar-rutas',
            'registrar-estatus-servicio',
            'reportar-incidencias',
        ];

        foreach ($permisos as $nombrePermiso) {
            Permission::firstOrCreate([
                'name' => $nombrePermiso,
                'guard_name' => $guard,
            ]);
        }

        // Matriz de permisos por rol
        $permisosPorRol = [
            'director_general' => [
                'crear-usuarios',
                'eliminar-usuarios',
                'crear-ordenes-servicio',
                'eliminar-ordenes-servicio',
                'visualizar-rutas',
                'registrar-estatus-servicio',
                'reportar-incidencias',
            ],
            'jefe_logistica' => [
                'crear-ordenes-servicio',
                'visualizar-rutas',
                'registrar-estatus-servicio',
                'reportar-incidencias',
            ],
            'ejecutivo_comercial' => [
                'visualizar-rutas',
                'reportar-incidencias',
            ],
            'operador' => [
                'registrar-estatus-servicio',
                'reportar-incidencias',
            ],
        ];

        // Crear o actualizar roles y asignarles sus permisos
        foreach ($permisosPorRol as $nombreRol => $listaPermisos) {
            $rol = Role::firstOrCreate([
                'name' => $nombreRol,
                'guard_name' => $guard,
            ]);
            $rol->syncPermissions($listaPermisos);
        }

        // Resetear caché después de crear y sincronizar permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $usuarios = [
            [
                'nombre' => 'Heriberto',
                'apellidos' => 'Robles Rivera',
                'telefono' => '7712226263',
                'email' => 'director_general@logymex.com',
                'password' => 'director_general2026',
                'rol' => 'director_general',
            ],
            [
                'nombre' => 'Ismael',
                'apellidos' => 'Barranco García',
                'telefono' => '7715553597',
                'email' => 'jefe_logistica@logymex.com',
                'password' => 'jefe_logistica2026',
                'rol' => 'jefe_logistica',
            ],
            [
                'nombre' => 'Edgar Alfonso',
                'apellidos' => 'Badillo Lopez',
                'telefono' => '7711283924',
                'email' => 'ejecutivo_comercial@logymex.com',
                'password' => 'ejecutivo_comercial2026',
                'rol' => 'ejecutivo_comercial',
            ],
            [
<<<<<<< HEAD
                'nombre' => 'Operador Logymex',
                'telefono' => '8180001111',
                'password' => 'password123',
=======
                'nombre' => 'Ángel Fernando',
                'apellidos' => 'Alejo Flores',
                'telefono' => '1234567893',
                'email' => null,
                'password' => 'operador2026',
>>>>>>> 6c520b196f7a6fe93dc8c5965e5abb67d6cdea03
                'rol' => 'operador',
            ],
        ];

        foreach ($usuarios as $datosUsuario) {
            $email = ! empty($datosUsuario['email']) ? $datosUsuario['email'] : null;
            $nombreRol = $datosUsuario['rol'];
            $permisosAsignados = $permisosPorRol[$nombreRol] ?? [];

            $criterioBusqueda = $email !== null
                ? ['email' => $email]
                : ['telefono' => $datosUsuario['telefono']];

<<<<<<< HEAD
            $llaveUnica = isset($datosUsuario['email'])
                ? ['email' => $datosUsuario['email']]
                : ['telefono' => $datosUsuario['telefono']];

            $usuario = User::updateOrCreate($llaveUnica, [
                'name' => $datosUsuario['nombre'],
                'email' => $datosUsuario['email'] ?? null,
                'telefono' => $datosUsuario['telefono'] ?? null,
                'password' => Hash::make($datosUsuario['password']),
            ]);
=======
            $usuario = User::updateOrCreate(
                $criterioBusqueda,
                [
                    'nombre' => $datosUsuario['nombre'],
                    'apellidos' => $datosUsuario['apellidos'],
                    'telefono' => $datosUsuario['telefono'],
                    'email' => $email,
                    'password' => Hash::make($datosUsuario['password']),
                    'rol' => $nombreRol,
                    'permisos' => $permisosAsignados,
                ]
            );
>>>>>>> 6c520b196f7a6fe93dc8c5965e5abb67d6cdea03

            $usuario->syncRoles([$nombreRol]);
        }
    }
}
