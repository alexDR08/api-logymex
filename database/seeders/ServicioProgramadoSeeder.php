<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\ServicioProgramado;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServicioProgramadoSeeder extends Seeder
{
    public function run(): void
    {
        $operador = User::where('telefono', '8180001111')->first();
        $hospital = Cliente::where('nombre', 'Hospital General del Norte')->first();
        $unidadRpbi = Unidad::where('numero_economico', 'U-01')->first();

        if ($hospital) {
            ServicioProgramado::updateOrCreate(
                [
                    'cliente_id' => $hospital->id,
                    'fecha' => now()->addWeek()->toDateString(),
                ],
                [
                    'unidad_id' => $unidadRpbi?->id,
                    'operador_id' => $operador?->id,
                    'hora' => '09:00',
                    'estatus' => 'pendiente',
                    'color_calendario' => 'amarillo',
                    'observaciones' => 'Recoleccion semanal programada de RPBI.',
                ]
            );
        }
    }
}
