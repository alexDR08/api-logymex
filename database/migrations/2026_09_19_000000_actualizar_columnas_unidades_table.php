<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            $table->dropColumn('autocargante');
            $table->string('marca')->nullable()->after('numero_economico');
            $table->string('tipo_servicio')->nullable()->after('modelo');
        });
    }

    public function down(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            $table->dropColumn(['marca', 'tipo_servicio']);
            $table->boolean('autocargante')->default(false)->after('modelo');
        });
    }
};
