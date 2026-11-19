<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    protected $table = 'opportunities';

    protected $guarded = [];

    protected $casts = [
        'next_follow_up_at' => 'datetime',
        'closed_at'         => 'datetime',
    ];

    // Estados posibles del ciclo de vida
    const STATUSES = [
        'por_contactar',
        'contactado',
        'muestra_entregada',
        'en_negociacion',
        'primer_pedido',
        'punto_venta_activo',
        'no_concretada',
    ];

    public function entrepreneurProfile(): BelongsTo
    {
        return $this->belongsTo(EntrepreneurProfile::class);
    }

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }
}
