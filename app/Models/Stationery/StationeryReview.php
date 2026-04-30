<?php

declare(strict_types=1);

namespace App\Models\Stationery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use App\Models\User;

final class StationeryReview extends Model
{
    protected $table = 'stationery_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'reviewable_id',
        'reviewable_type',
        'user_id',
        'rating',
        'comment',
        'photos',
        'correlation_id',
    ];

    protected $casts = [
        'photos' => 'json',
        'rating' => 'integer',
    ];

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted(): void
    {
        self::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            if ($this->guard->check() && empty($model->tenant_id)) {
                $model->tenant_id = $this->guard->user()->tenant_id;
            }
        });

        self::addGlobalScope('tenant', function ($builder) {
            if ($this->guard->check()) {
                $builder->where('tenant_id', $this->guard->user()->tenant_id);
            }
        });
    }
}
