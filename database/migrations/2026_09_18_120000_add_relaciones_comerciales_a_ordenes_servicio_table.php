<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->foreignId('ejecutivo_ventas_id')->nullable()->after('operador_id')->constrained('users')->nullOnDelete();
            $table->string('tipo_servicio')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ejecutivo_ventas_id');
            $table->dropColumn('tipo_servicio');
        });
    }
};
