<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

final class ShipmentRating extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'shipment_ratings';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'shipment_id',
        'reviewer_id',
        'rating',
        'comment',
        'media',
        'verified_purchase',
        'correlation_id',
    ];

    protected $casts = [
        'media' => 'collection',
        'verified_purchase' => 'boolean',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted_disabled(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()?->id);
            }
        });
    }
}
