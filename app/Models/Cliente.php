<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'domicilio', 'telefono', 'correo', 'contacto'])]
class Cliente extends Model
{
    use HasFactory;

    public function ordenesServicio(): HasMany
    {
        return $this->hasMany(OrdenServicio::class);
    }
}
