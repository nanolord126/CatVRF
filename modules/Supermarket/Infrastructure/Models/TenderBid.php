<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TenderBid extends Model
{
    use SoftDeletes;

    protected $table = 'supermarket_tender_bids';

    protected $fillable = [
        'uuid',
        'tender_id',
        'tender_lot_id',
        'supplier_id',
        'supplier_tier_id',
        'offered_price',
        'offered_quantity',
        'unit',
        'available_from',
        'delivery_date',
        'guarantee_letter_path',
        'attached_documents',
        'status',
        'rating',
        'review_comment',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'offered_price' => 'decimal:2',
        'offered_quantity' => 'decimal:3',
        'available_from' => 'date',
        'delivery_date' => 'date',
        'attached_documents' => 'array',
        'rating' => 'decimal:2',
        'metadata' => 'array',
    ];

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class, 'tender_id');
    }

    public function tenderLot(): BelongsTo
    {
        return $this->belongsTo(TenderLot::class, 'tender_lot_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'supplier_id');
    }

    public function supplierTier(): BelongsTo
    {
        return $this->belongsTo(\Modules\Supermarket\Domain\Models\SupplierTier::class, 'supplier_tier_id');
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
