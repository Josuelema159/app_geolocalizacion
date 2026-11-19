<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $guarded = [];

    protected $casts = [
        'precio_comercio' => 'float',
        'precio_sugerido' => 'float',
    ];

    public function emprendedor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}