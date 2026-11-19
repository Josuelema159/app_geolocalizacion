<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'wholesale_price'          => 'float',
        'suggested_retail_price'   => 'float',
        'minimum_order'            => 'float',
        'weekly_capacity'          => 'float',
        'required_temperature_min' => 'float',
        'required_temperature_max' => 'float',
        'coverage_zones'           => 'array',
        'documentation'            => 'array',
        'is_public'                => 'boolean',
    ];

    public function entrepreneurProfile(): BelongsTo
    {
        return $this->belongsTo(EntrepreneurProfile::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function proposals(): BelongsToMany
    {
        return $this->belongsToMany(Proposal::class, 'proposal_products')
                    ->withPivot('quantity', 'quoted_price');
    }
}
