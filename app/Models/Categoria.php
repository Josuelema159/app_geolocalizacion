<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $guarded = [];

    protected $casts = [
        'requiere_refrigeracion' => 'boolean',
    ];

    public function establecimientos()
    {
        return $this->belongsToMany(Establecimiento::class, 'establecimiento_categoria')
                    ->withPivot('tipo_relacion', 'detalle_busqueda')
                    ->withTimestamps();
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}