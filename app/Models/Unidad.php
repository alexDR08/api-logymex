<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

<<<<<<< HEAD
#[Fillable(['operador_id', 'placas', 'numero_economico', 'marca', 'modelo', 'tipo_servicio', 'estatus'])]
=======
#[Fillable(['operador_id', 
            'placas', 
            'numero_economico', 
            'modelo', 
            'estatus'])]
            
>>>>>>> 6c520b196f7a6fe93dc8c5965e5abb67d6cdea03
class Unidad extends Model
{
    use HasFactory;

    protected $table = 'unidades';

    public function operador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operador_id');
    }

    public function rutas(): HasMany
    {
        return $this->hasMany(Ruta::class);
    }

    public function ordenesServicio(): HasMany
    {
        return $this->hasMany(OrdenServicio::class);
    }
}
