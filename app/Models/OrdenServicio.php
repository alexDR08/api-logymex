<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'cliente_id',
    'ruta_id',
    'unidad_id',
    'operador_id',
    'folio',
    'descripcion',
    'programacion',
    'estatus',
    'observaciones',
    'completado_en',
    'cancelado_en',
])]
class OrdenServicio extends Model
{
    use HasFactory;

    protected $table = 'ordenes_servicio';

    protected function casts(): array
    {
        return [
            'programacion' => 'datetime',
            'completado_en' => 'datetime',
            'cancelado_en' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class);
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class);
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operador_id');
    }

    public function residuos(): BelongsToMany
    {
        return $this->belongsToMany(Residuo::class, 'orden_servicio_residuo')
            ->withPivot(['cantidad', 'unidad_medida'])
            ->withTimestamps();
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencia::class);
    }
}
