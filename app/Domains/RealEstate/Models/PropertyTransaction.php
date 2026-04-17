<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domains\RealEstate\Domain\Entities\RealEstateAgent;

final class PropertyTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'real_estate_transactions';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'property_id',
        'buyer_id',
        'seller_id',
        'agent_id',
        'payment_transaction_id',
        'amount',
        'currency',
        'status',
        'escrow_hold_until',
        'released_at',
        'refunded_at',
        'is_b2b',
        'commission_rate',
        'commission_amount',
        'split_config',
        'release_reason',
        'refund_reason',
        'metadata',
        'tags',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'escrow_hold_until' => 'datetime',
        'released_at' => 'datetime',
        'refunded_at' => 'datetime',
        'is_b2b' => 'boolean',
        'split_config' => 'json',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'seller_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(RealEstateAgent::class, 'agent_id');
    }

    public function scopePendingEscrow($query)
    {
        return $query->where('status', 'escrow_pending');
    }

    public function scopeReleased($query)
    {
        return $query->where('status', 'escrow_released');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'escrow_refunded');
    }

    public function scopeB2b($query)
    {
        return $query->where('is_b2b', true);
    }

    public function scopeB2c($query)
    {
        return $query->where('is_b2b', false);
    }
}
