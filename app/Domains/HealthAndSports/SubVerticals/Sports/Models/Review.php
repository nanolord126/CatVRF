<?php

declare(strict_types=1);

namespace App\Domains\Sports\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;

final class Review extends Model
{
    use TenantScoped;

    protected $table = 'reviews';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'studio_id',
        'trainer_id',
        'reviewer_id',
        'booking_id',
        'rating',
        'title',
        'content',
        'categories',
        'verified_purchase',
        'published_at',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'categories' => AsCollection::class,
        'tags' => AsCollection::class,
        'verified_purchase' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
