<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceFeature extends Model
{
    protected $fillable = [
        'feature_key',
        'slug',
        'title',
        'description',
        'items',
        'sort_order',
        'is_active',
        // Hero
        'headline',
        'hero_description',
        // Overview section
        'overview_title',
        'overview_description',
        'overview',
        // Approach section — stored under the legacy process_* columns
        'process_title',
        'process_steps',
        // Technologies section
        'technologies',
        // Optional per-feature CTA override
        'cta_title',
        'cta_description',
        'cta_button_text',
        'cta_button_url',
        // SEO
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'json_ld_schema',
        // Legacy
        'field_types',
    ];

    protected function casts(): array
    {
        return [
            'items'          => 'array',
            'is_active'      => 'boolean',
            'process_steps'  => 'array',
            'field_types'    => 'array',
            'overview'       => 'array',
            'technologies'   => 'array',
            'meta_keywords'  => 'array',
            'json_ld_schema' => 'array',
        ];
    }

    public function media(): HasMany
    {
        return $this->hasMany(ServiceFeatureMedia::class)->orderBy('sort_order');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_feature_assignments')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('service_feature_assignments.sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('title');
    }
}
