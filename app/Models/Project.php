<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'description',
        'featured_image',
        'client',
        'category',
        'duration',
        'year',
        'live_url',
        'tech_stack',
        'tags',
        'gallery',
        'highlights',
        'key_features',
        'author_id',
        'status',
        'published_at',
        'is_featured',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'json_ld_schema',
    ];

    protected function casts(): array
    {
        return [
            'tech_stack'     => 'array',
            'tags'           => 'array',
            'gallery'        => 'array',
            'highlights'     => 'array',
            'key_features'   => 'array',
            'meta_keywords'  => 'array',
            'json_ld_schema' => 'array',
            'is_featured'    => 'boolean',
            'published_at'   => 'datetime',
            'year'           => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                     ->where(function ($q) {
                         $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                     });
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'published' => ['label' => 'Published', 'color' => 'green'],
            'draft'     => ['label' => 'Draft', 'color' => 'gray'],
            'archived'  => ['label' => 'Archived', 'color' => 'yellow'],
            default     => ['label' => ucfirst((string) $this->status), 'color' => 'gray'],
        };
    }
}
