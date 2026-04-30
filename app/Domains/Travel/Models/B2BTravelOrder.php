<?php

declare(strict_types=1);

namespace App\Domains\Travel\Models;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class B2BTravelOrder extends Model
{
    use TenantScoped;

    protected $table = 'b2b_travel_orders';

    protected $fillable = [
        'uuid', 'tenant_id', 'b2b_travel_storefront_id', 'user_id', 'order_number',
        'company_contact_person', 'company_phone', 'items', 'total_amount',
        'commission_amount', 'status', 'rejection_reason', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'items' => 'json',
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'tags' => 'json',
    ];

    public function storefront(): BelongsTo
    {
        return $this->belongsTo(B2BTravelStorefront::class, 'b2b_travel_storefront_id');
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
            if (function_exists('tenant') && tenant() && tenant()->id) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
