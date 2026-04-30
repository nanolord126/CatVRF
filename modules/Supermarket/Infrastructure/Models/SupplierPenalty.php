<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SupplierPenalty extends Model
{
    use SoftDeletes;

    protected $table = 'supermarket_supplier_penalties';

    protected $fillable = [
        'uuid',
        'supplier_id',
        'affected_business_id',
        'document_id',
        'supply_chain_link_id',
        'supply_amount',
        'penalty_amount',
        'platform_share',
        'business_share',
        'status',
        'reason',
        'expired_products',
        'expired_quantity',
        'charged_at',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'supply_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'platform_share' => 'decimal:2',
        'business_share' => 'decimal:2',
        'expired_products' => 'array',
        'expired_quantity' => 'integer',
        'charged_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_CHARGED = 'charged';
    public const STATUS_PAID = 'paid';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_WAIVED = 'waived';

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'supplier_id');
    }

    public function affectedBusiness(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'affected_business_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(\Modules\Supermarket\Domain\Models\Document::class, 'document_id');
    }

    public function supplyChainLink(): BelongsTo
    {
        return $this->belongsTo(\Modules\Supermarket\Domain\Models\SupplyChainLink::class, 'supply_chain_link_id');
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function markAsCharged(): bool
    {
        $this->status = self::STATUS_CHARGED;
        $this->charged_at = now();
        return $this->save();
    }

    public function markAsPaid(): bool
    {
        $this->status = self::STATUS_PAID;
        $this->paid_at = now();
        return $this->save();
    }
}
