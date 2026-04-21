<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingPlan extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'custom_price_label',
        'is_highlighted',
        'highlight_label',
        'cta_text',
        'cta_url',
        'features',
        'sort_order',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_highlighted' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the prices for this plan.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(PricingPlanPrice::class);
    }

    /**
     * Get the price for a specific billing period.
     */
    public function getPrice(string $billingPeriod): ?PricingPlanPrice
    {
        return $this->prices()->where('billing_period', $billingPeriod)->first();
    }

    /**
     * Scope to get only active plans.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc');
    }
}
