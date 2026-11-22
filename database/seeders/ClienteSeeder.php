<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = [
            [
                'nombre' => 'Hospital General del Norte',
                'domicilio' => 'Av. Constitucion 450, Monterrey, N.L.',
                'telefono' => '8181234567',
                'correo' => 'contacto@hospitalnorte.test',
                'contacto' => 'Marta Reyes',
                'es_preferente' => true,
                'estatus' => 'activo',
            ],
            [
                'nombre' => 'Restaurante La Central',
                'domicilio' => 'Calz. del Valle 120, San Pedro Garza Garcia, N.L.',
                'telefono' => '8187654321',
                'correo' => 'admin@lacentral.test',
                'contacto' => 'Roberto Salas',
                'es_preferente' => false,
                'estatus' => 'activo',
            ],
        ];

        foreach ($clientes as $datos) {
            Cliente::updateOrCreate(['nombre' => $datos['nombre']], $datos);
        }
    }
}
