<?php

namespace Database\Seeders;

use App\Models\Residuo;
use Illuminate\Database\Seeder;

class ResiduoSeeder extends Seeder
{
    public function run(): void
    {
        $residuos = [
            ['nombre_residuo' => 'Residuos Biologico-Infecciosos (RPBI)', 'clasificacion' => 'peligroso'],
            ['nombre_residuo' => 'Aceites y grasas residuales', 'clasificacion' => 'peligroso'],
            ['nombre_residuo' => 'Residuos solidos urbanos', 'clasificacion' => 'no_peligroso'],
        ];

        foreach ($residuos as $datos) {
            Residuo::updateOrCreate(['nombre_residuo' => $datos['nombre_residuo']], $datos);
        }
    }
}
