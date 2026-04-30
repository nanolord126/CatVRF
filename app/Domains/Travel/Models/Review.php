<?php

declare(strict_types=1);

namespace App\Domains\Travel\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;

final class Review extends Model
{
    use TenantScoped;

    protected $table = 'travel_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'booking_id',
        'user_id',
        'rating',
        'comment',
        'photos',
        'is_verified',
        'correlation_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified' => 'boolean',
        'photos' => 'json',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        self::creating(function (Review $model) {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
            if (! $model->tenant_id) {
                $model->tenant_id = (tenant()->id ?? 1);
            }
            if (! $model->correlation_id) {
                $model->correlation_id = (string) Str::uuid();
            }
        });

        self::addGlobalScope('tenant', function ($builder) {
            $builder->where('tenant_id', tenant()->id ?? 1);
        });
    }
}
