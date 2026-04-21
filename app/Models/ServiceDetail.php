<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceDetail extends Model
{
    protected $fillable = [
        'service_id',
        'hero_label',
        'hero_headline',
        'hero_subtitle',
        'media_type',
        'process_title',
        'cta_title',
        'cta_description',
        'cta_button_text',
        'is_coming_soon',
        'coming_soon_description',
        'field_types',
    ];

    protected function casts(): array
    {
        return [
            'hero_headline' => 'array',
            'field_types' => 'array',
            'is_coming_soon' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
