<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nombre_residuo', 'clasificacion'])]
class Residuo extends Model
{
    use HasFactory;

    protected $table = 'residuos';

    public function ordenesServicio(): BelongsToMany
    {
        return $this->belongsToMany(OrdenServicio::class, 'orden_servicio_residuo')
            ->withPivot(['cantidad', 'unidad_medida'])
            ->withTimestamps();
    }
}
