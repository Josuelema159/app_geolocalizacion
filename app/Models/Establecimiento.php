<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Establecimiento extends Model
{
    protected $guarded = [];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
        'verificado' => 'boolean',
        'ultima_verificacion' => 'datetime',
    ];

    public function propietario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'establecimiento_categoria')
                    ->withPivot('tipo_relacion', 'detalle_busqueda')
                    ->withTimestamps();
    }

    public function oportunidades()
    {
        return $this->hasMany(Oportunidad::class);
    }

    public function reportes()
    {
        return $this->hasMany(ReporteEstablecimiento::class);
    }
}