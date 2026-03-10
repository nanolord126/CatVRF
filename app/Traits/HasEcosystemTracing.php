<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Context;

/**
 * Trait for end-to-end event tracing across the business ecosystem.
 */
trait HasEcosystemTracing
{
    public static function bootHasEcosystemTracing()
    {
        static::creating(function ($model) {
            // Ensure every database mutation has a correlation_id for AI audit
            if (empty($model->correlation_id)) {
                $model->correlation_id = Context::get('correlation_id') ?? (string) Str::uuid();
            }
        });
    }

    public function scopeByCorrelation($query, $id)
    {
        return $query->where('correlation_id', $id);
    }
}
