<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Proposal extends Model
{
    protected $guarded = [];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    // Respuestas posibles del comercio
    const MERCHANT_RESPONSES = [
        'enviada',
        'me_interesa',
        'solicito_informacion',
        'agendar_visita',
        'no_interesado',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'proposal_products')
                    ->withPivot('quantity', 'quoted_price');
    }
}
