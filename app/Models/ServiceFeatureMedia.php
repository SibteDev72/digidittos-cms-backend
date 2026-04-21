<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceFeatureMedia extends Model
{
    protected $fillable = [
        'service_feature_id',
        'type',
        'primary_url',
        'secondary_url',
        'sort_order',
    ];

    public function feature(): BelongsTo
    {
        return $this->belongsTo(ServiceFeature::class, 'service_feature_id');
    }
}
