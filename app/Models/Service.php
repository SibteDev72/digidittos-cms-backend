<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $fillable = [
        'number',
        'title',
        'short_title',
        'slug',
        'description',
        'video_url',
        'route',
        'is_available',
        'sort_order',
        'is_active',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'og_image',
        'json_ld_schema',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'is_active' => 'boolean',
            'json_ld_schema' => 'array',
        ];
    }

    public function detail(): HasOne
    {
        return $this->hasOne(ServiceDetail::class);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(ServiceFeature::class, 'service_feature_assignments')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('service_feature_assignments.sort_order');
    }

    public function processSteps(): HasMany
    {
        return $this->hasMany(ServiceProcessStep::class)->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }
}
