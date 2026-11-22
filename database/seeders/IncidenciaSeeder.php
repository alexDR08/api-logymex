<?php

namespace Database\Seeders;

use App\Models\Incidencia;
use App\Models\OrdenServicio;
use App\Models\Ruta;
use App\Models\User;
use Illuminate\Database\Seeder;

class IncidenciaSeeder extends Seeder
{
    public function run(): void
    {
        $operador = User::where('telefono', '8180001111')->first();
        $ordenRestaurante = OrdenServicio::where('folio', 'OS-0002')->first();
        $rutaRestaurante = Ruta::where('orden_servicio_id', $ordenRestaurante?->id)->first();

        if ($ordenRestaurante) {
            Incidencia::updateOrCreate(
                [
                    'orden_servicio_id' => $ordenRestaurante->id,
                    'motivo' => 'Acceso bloqueado',
                ],
                [
                    'ruta_id' => $rutaRestaurante?->id,
                    'operador_id' => $operador?->id,
                    'fecha' => now()->toDateString(),
                    'descripcion' => 'El cliente cerro el local antes de la hora programada, no se pudo dar servicio.',
                    'estatus' => 'registrada',
                ]
            );
        }
    }
}
