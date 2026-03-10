<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasAutomation2026
{
    /**
     * Boot the trait to support Correlation ID and Metadata tracking.
     */
    protected static function bootHasAutomation2026(): void
    {
        static::creating(function ($model) {
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Helper for AI embeddings and ML metadata storage.
     */
    public function updateAiMetadata(array $data): void
    {
        $current = $this->metadata ?? [];
        $this->metadata = array_merge($current, $data);
        $this->save();
    }
}
