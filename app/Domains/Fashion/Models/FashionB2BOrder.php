<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class FashionB2BOrder extends Model
{
    use TenantScoped;

    protected $table = 'fashion_b2b_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'fashion_store_id',
        'buyer_inn',
        'total_amount',
        'status',
        'items_json',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'items_json' => 'json',
        'metadata' => 'json',
        'total_amount' => 'integer',
        'tenant_id' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(FashionStore::class, 'fashion_store_id');
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

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
