<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

<<<<<<< HEAD
#[Fillable(['cliente_id', 'creado_por_id', 'operador_id', 'ejecutivo_ventas_id', 'folio', 'descripcion', 'tipo_servicio', 'fecha_programada', 'hora_programada', 'estatus', 'observaciones', 'completado_en', 'cancelado_en'])]
=======
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
>>>>>>> 6c520b196f7a6fe93dc8c5965e5abb67d6cdea03
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

<<<<<<< HEAD
    public function ejecutivoVentas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ejecutivo_ventas_id');
    }

    public function rutas(): HasMany
=======
    public function residuos(): BelongsToMany
>>>>>>> 6c520b196f7a6fe93dc8c5965e5abb67d6cdea03
    {
        return $this->belongsToMany(Residuo::class, 'orden_servicio_residuo')
            ->withPivot(['cantidad', 'unidad_medida'])
            ->withTimestamps();
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
