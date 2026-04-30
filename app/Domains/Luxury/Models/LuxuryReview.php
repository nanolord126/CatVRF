<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class LuxuryReview extends Model
{
    use TenantScoped;

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

    public function client(): BelongsTo
    {
        return $this->belongsTo(LuxuryClient::class, 'client_id');
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted_disabled(): void
    {
        self::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });

        self::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('luxury_reviews.tenant_id', tenant()->id);
            }
        });
    }
}
