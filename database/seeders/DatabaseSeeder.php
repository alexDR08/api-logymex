<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UsuarioSeeder::class,
            ClienteSeeder::class,
            UnidadSeeder::class,
            ResiduoSeeder::class,
            OrdenServicioSeeder::class,
            RutaSeeder::class,
            IncidenciaSeeder::class,
            ServicioProgramadoSeeder::class,
        ]);
    }
}
