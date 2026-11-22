<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\OrdenServicio;
use App\Models\Residuo;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrdenServicioSeeder extends Seeder
{
    public function run(): void
    {
        $jefeLogistica = User::where('email', 'logistica@logymex.test')->first();
        $ejecutivoVentas = User::where('email', 'ventas@logymex.test')->first();
        $operador = User::where('telefono', '8180001111')->first();

        $hospital = Cliente::where('nombre', 'Hospital General del Norte')->first();
        $restaurante = Cliente::where('nombre', 'Restaurante La Central')->first();

        $rpbi = Residuo::where('nombre_residuo', 'Residuos Biologico-Infecciosos (RPBI)')->first();
        $grasas = Residuo::where('nombre_residuo', 'Aceites y grasas residuales')->first();

        $ordenHospital = OrdenServicio::updateOrCreate(
            ['folio' => 'OS-0001'],
            [
                'cliente_id' => $hospital?->id,
                'creado_por_id' => $jefeLogistica?->id,
                'operador_id' => $operador?->id,
                'ejecutivo_ventas_id' => $ejecutivoVentas?->id,
                'descripcion' => 'Recoleccion y acopio temporal de RPBI en punto de generacion.',
                'tipo_servicio' => 'RPBI',
                'fecha_programada' => now()->addDay()->toDateString(),
                'hora_programada' => '09:00',
                'estatus' => 'asignado',
            ]
        );

        if ($rpbi) {
            $ordenHospital->residuos()->syncWithoutDetaching([
                $rpbi->id => ['cantidad' => 35, 'unidad_medida' => 'kg'],
            ]);
        }

        $ordenRestaurante = OrdenServicio::updateOrCreate(
            ['folio' => 'OS-0002'],
            [
                'cliente_id' => $restaurante?->id,
                'creado_por_id' => $jefeLogistica?->id,
                'operador_id' => $operador?->id,
                'ejecutivo_ventas_id' => $ejecutivoVentas?->id,
                'descripcion' => 'Drenaje y limpieza de trampa de grasa.',
                'tipo_servicio' => 'drenaje_trampa_grasa',
                'fecha_programada' => now()->addDays(2)->toDateString(),
                'hora_programada' => '11:30',
                'estatus' => 'pendiente',
            ]
        );

        if ($grasas) {
            $ordenRestaurante->residuos()->syncWithoutDetaching([
                $grasas->id => ['cantidad' => 120, 'unidad_medida' => 'lts'],
            ]);
        }
    }
}
