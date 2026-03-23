<?php

declare(strict_types=1);

namespace App\Domains\Appointments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $bookable_type
 * @property int $bookable_id
 * @property int $client_id
 * @property string $datetime_start
 * @property string $datetime_end
 * @property int $price_cents
 * @property string $status
 * @property string $payment_status
 * @property string|null $correlation_id
 * @property array|null $tags
 */
final class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'appointments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'bookable_type',
        'bookable_id',
        'client_id',
        'datetime_start',
        'datetime_end',
        'price_cents',
        'status',
        'payment_status',
        'correlation_id',
        'tags',
        'notes',
    ];

    protected $casts = [
        'tags' => 'json',
        'datetime_start' => 'datetime',
        'datetime_end' => 'datetime',
        'price_cents' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $appointment) {
            $appointment->uuid = $appointment->uuid ?? (string) Str::uuid();
            $appointment->status = $appointment->status ?? 'pending';
            $appointment->payment_status = $appointment->payment_status ?? 'unpaid';
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', tenant('id'));
            }
        });
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}
