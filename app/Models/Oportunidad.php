<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Oportunidad extends Model
{
    protected $table = 'oportunidades';
    protected $guarded = [];

    protected $casts = [
        'proxima_gestion' => 'date',
    ];

    public function emprendedor()
    {
        return $this->belongsTo(User::class, 'emprendedor_id');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}