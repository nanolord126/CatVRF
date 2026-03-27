<?php

declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * MusicReview model representing a review for an instrument, studio, or teacher.
 */
final class MusicReview extends Model
{
    use HasFactory;

    protected $table = 'music_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'correlation_id',
        'user_id',
        'music_store_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'comment',
        'media',
        'is_published',
        'tags',
    ];

    protected $casts = [
        'media' => 'json',
        'tags' => 'array',
        'rating' => 'integer',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());
            
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = tenant()->id ?? 'null';
            }
        });

        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('music_reviews.tenant_id', tenant()->id);
            }
        });
    }

    public function reviewable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(MusicStore::class, 'music_store_id');
    }
}
