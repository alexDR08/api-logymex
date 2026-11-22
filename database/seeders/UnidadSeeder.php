<?php

namespace Database\Seeders;

use App\Models\Unidad;
use App\Models\User;
use Illuminate\Database\Seeder;

class UnidadSeeder extends Seeder
{
    public function run(): void
    {
        $operador = User::where('telefono', '8180001111')->first();

        $unidades = [
            [
                'placas' => 'LGX-101',
                'numero_economico' => 'U-01',
                'marca' => 'Ford',
                'modelo' => 'F-350',
                'tipo_servicio' => 'RPBI',
                'estatus' => 'disponible',
            ],
            [
                'placas' => 'LGX-102',
                'numero_economico' => 'U-02',
                'marca' => 'Ford',
                'modelo' => 'Delivery',
                'tipo_servicio' => 'recoleccion_liquidos',
                'estatus' => 'disponible',
            ],
            [
                'placas' => 'LGX-103',
                'numero_economico' => 'U-03',
                'marca' => 'International',
                'modelo' => 'Torton',
                'tipo_servicio' => null,
                'estatus' => 'disponible',
            ],
            [
                'placas' => 'LGX-104',
                'numero_economico' => 'U-04',
                'marca' => 'Vactor',
                'modelo' => '2100 Plus',
                'tipo_servicio' => 'succion_liquidos',
                'estatus' => 'disponible',
            ],
        ];

        foreach ($unidades as $datos) {
            $datos['operador_id'] = $operador?->id;
            Unidad::updateOrCreate(['numero_economico' => $datos['numero_economico']], $datos);
        }
    }
}
