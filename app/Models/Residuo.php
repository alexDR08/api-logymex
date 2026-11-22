<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Factories\HasFactory;
>>>>>>> 6c520b196f7a6fe93dc8c5965e5abb67d6cdea03
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nombre_residuo', 'clasificacion'])]
class Residuo extends Model
{
<<<<<<< HEAD
    public function ordenesServicio(): BelongsToMany
    {
        return $this->belongsToMany(OrdenServicio::class, 'orden_servicio_residuo')
            ->withPivot(['cantidad', 'unidad_medida', 'observaciones'])
=======
    use HasFactory;

    protected $table = 'residuos';

    public function ordenesServicio(): BelongsToMany
    {
        return $this->belongsToMany(OrdenServicio::class, 'orden_servicio_residuo')
            ->withPivot(['cantidad', 'unidad_medida'])
>>>>>>> 6c520b196f7a6fe93dc8c5965e5abb67d6cdea03
            ->withTimestamps();
    }
}
