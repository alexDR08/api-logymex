<?php

namespace Database\Seeders;

use App\Models\OrdenServicio;
use App\Models\Ruta;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Database\Seeder;

class RutaSeeder extends Seeder
{
    public function run(): void
    {
        $operador = User::where('telefono', '8180001111')->first();

        $ordenHospital = OrdenServicio::where('folio', 'OS-0001')->first();
        $ordenRestaurante = OrdenServicio::where('folio', 'OS-0002')->first();

        $unidadRpbi = Unidad::where('numero_economico', 'U-01')->first();
        $unidadDelivery = Unidad::where('numero_economico', 'U-02')->first();

        if ($ordenHospital) {
            Ruta::updateOrCreate(
                ['orden_servicio_id' => $ordenHospital->id],
                [
                    'unidad_id' => $unidadRpbi?->id,
                    'operador_id' => $operador?->id,
                    'domicilio' => 'Av. Constitucion 450, Monterrey, N.L.',
                    'fecha' => now()->addDay()->toDateString(),
                    'hora_inicio' => '09:00',
                    'estatus' => 'iniciada',
                ]
            );
        }

        if ($ordenRestaurante) {
            Ruta::updateOrCreate(
                ['orden_servicio_id' => $ordenRestaurante->id],
                [
                    'unidad_id' => $unidadDelivery?->id,
                    'operador_id' => $operador?->id,
                    'domicilio' => 'Calz. del Valle 120, San Pedro Garza Garcia, N.L.',
                    'fecha' => now()->addDays(2)->toDateString(),
                    'hora_inicio' => '11:30',
                    'estatus' => 'pendiente',
                ]
            );
        }
    }
}
