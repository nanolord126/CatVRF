<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * LuxuryReview
 *
 * Layer 1: Model Layer
 * Хранит отзывы по товарам и услугам элитного сегмента.
 *
 * @version 1.0.0
 * @author CatVRF
 */
final class LuxuryReview extends Model
{
    use SoftDeletes;

    protected $table = 'luxury_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'client_id',
        'reviewable_type',
        'reviewable_id',
        'rating', // 1-5
        'comment',
        'private_notes', // заметки консьержа о впечатлениях клиента
        'is_verified', // подтверждение владения/использования
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('luxury_reviews.tenant_id', tenant()->id);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(LuxuryClient::class, 'client_id');
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
