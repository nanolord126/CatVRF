<?php

declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Models;

use Carbon\CarbonImmutable;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class FashionRetailReturn extends Model
{
    protected $table = 'fashion_retail_returns';

    protected $fillable = [
        'uuid',
        'order_id',
        'product_id',
        'reason',
        'status',
        'refund_amount',
        'images',
        'notes',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'images' => 'json',
        'tags' => 'json',
        'refund_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(FashionRetailOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionRetailProduct::class, 'product_id');
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
        parent::booted();
        self::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
