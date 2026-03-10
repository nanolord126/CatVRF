<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Trait for automatic correlation_id injection into all model mutations.
 */
trait HasCorrelationId
{
    public static function bootHasCorrelationId()
    {
        static::creating(function ($model) {
            $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
        });
    }
}
