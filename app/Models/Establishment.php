<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Establishment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'latitude'                 => 'float',
        'longitude'                => 'float',
        'has_refrigeration'        => 'boolean',
        'attention_hours'          => 'array',
        'supplier_attention_hours' => 'array',
        'entry_requirements'       => 'array',
        'purchase_conditions'      => 'array',
        'weekly_purchase_volume'   => 'float',
        'verified_at'              => 'datetime',
        'last_verified_at'         => 'datetime',
        'privacy_consent_at'       => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'establishment_categories');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(EstablishmentReport::class);
    }
}
