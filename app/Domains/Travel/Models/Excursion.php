<?php

declare(strict_types=1);

namespace App\Domains\Travel\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Excursion extends Model
{
    use TenantScoped;

    protected $table = 'excursions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'destination_id',
        'name',
        'description',
        'price',
        'duration_minutes',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'price' => 'integer',
        'duration_minutes' => 'integer',
        'tags' => 'json',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    protected static function booted(): void
    {
        self::creating(function (Excursion $model) {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
            if (! $model->tenant_id) {
                $model->tenant_id = (tenant()->id ?? 1);
            }
            if (! $model->correlation_id) {
                $model->correlation_id = $this->request->header('X-Correlation-ID');
            }
        });

        self::addGlobalScope('tenant', function ($builder) {
            $builder->where('tenant_id', tenant()->id ?? 1);
        });
    }
}
