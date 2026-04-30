<?php

declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class MusicReview extends Model
{
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

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(MusicStore::class, 'music_store_id');
    }

    protected static function booted_disabled(): void
    {
        self::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) Str::uuid());

            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = tenant()->id ?? 'null';
            }
        });

        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('music_reviews.tenant_id', tenant()->id);
            }
        });
    }
}
