<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteEstablecimiento extends Model
{
    protected $table = 'reportes_establecimientos';
    protected $guarded = [];

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}