<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['cliente_id', 'creado_por_id', 'operador_id', 'ejecutivo_ventas_id', 'folio', 'descripcion', 'tipo_servicio', 'fecha_programada', 'hora_programada', 'estatus', 'observaciones', 'completado_en', 'cancelado_en'])]
class OrdenServicio extends Model
{
    use HasFactory;

    protected $table = 'ordenes_servicio';

    protected function casts(): array
    {
        return [
            'fecha_programada' => 'date',
            'completado_en' => 'datetime',
            'cancelado_en' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operador_id');
    }

    public function ejecutivoVentas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ejecutivo_ventas_id');
    }

    public function rutas(): HasMany
    {
        return $this->hasMany(Ruta::class);
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencia::class);
    }

    public function residuos(): BelongsToMany
    {
        return $this->belongsToMany(Residuo::class, 'orden_servicio_residuo')
            ->withPivot(['cantidad', 'unidad_medida', 'observaciones'])
            ->withTimestamps();
    }
}
