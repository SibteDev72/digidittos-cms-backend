<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingPlanPrice extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'pricing_plan_id',
        'billing_period',
        'price',
        'currency',
        'sale_price',
        'sale_percentage',
        'sale_label',
        'sale_starts_at',
        'sale_ends_at',
        'is_active',
        'square_plan_variation_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the pricing plan that owns this price.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class, 'pricing_plan_id');
    }

    /**
     * Check if this price has an active plan-level sale.
     */
    public function hasActiveSale(): bool
    {
        if (!$this->sale_price && !$this->sale_percentage) {
            return false;
        }

        if ($this->sale_starts_at && $this->sale_starts_at->gt(now())) {
            return false;
        }

        if ($this->sale_ends_at && $this->sale_ends_at->lt(now())) {
            return false;
        }

        return true;
    }

    /**
     * Get the effective price after applying any active sales.
     *
     * @return array{original_price: string, final_price: string, currency: string, on_sale: bool, sale_label: string|null, sale_source: string|null}
     */
    public function getEffectivePrice(): array
    {
        $originalPrice = $this->price;
        $currency = $this->currency ?? '$';

        // Check plan-level sale first
        if ($this->hasActiveSale()) {
            if ($this->sale_price) {
                $finalPrice = $this->sale_price;
            } else {
                $finalPrice = round($originalPrice * (1 - $this->sale_percentage / 100), 2);
            }

            return [
                'original_price' => number_format($originalPrice, 2, '.', ''),
                'final_price' => number_format($finalPrice, 2, '.', ''),
                'currency' => $currency,
                'on_sale' => true,
                'sale_label' => $this->sale_label,
                'sale_source' => 'plan',
                'square_plan_variation_id' => $this->square_plan_variation_id,
            ];
        }

        // Check category-level sale
        $categorySale = PricingCategorySale::getActiveSaleFor($this->billing_period);

        if ($categorySale) {
            $finalPrice = round($originalPrice * (1 - $categorySale->discount_percentage / 100), 2);

            return [
                'original_price' => number_format($originalPrice, 2, '.', ''),
                'final_price' => number_format($finalPrice, 2, '.', ''),
                'currency' => $currency,
                'on_sale' => true,
                'sale_label' => $categorySale->label,
                'sale_source' => 'category',
                'square_plan_variation_id' => $this->square_plan_variation_id,
            ];
        }

        // No sale active
        return [
            'original_price' => number_format($originalPrice, 2, '.', ''),
            'final_price' => number_format($originalPrice, 2, '.', ''),
            'currency' => $currency,
            'on_sale' => false,
            'sale_label' => null,
            'sale_source' => null,
            'square_plan_variation_id' => $this->square_plan_variation_id,
        ];
    }
}
