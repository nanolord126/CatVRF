<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class TenderBid extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'supplier_id',
        'manager_id',
        'tenant_id',
        'bid_amount',
        'proposal',
        'terms',
        'status',
        'submitted_at',
        'withdrawn_at',
        'reviewed_at',
        'rejection_reason',
        'guarantee_amount',
        'guarantee_frozen_at',
        'guarantee_released_at',
    ];

    protected $casts = [
        'bid_amount' => 'decimal:2',
        'guarantee_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'guarantee_frozen_at' => 'datetime',
        'guarantee_released_at' => 'datetime',
        'terms' => 'array',
    ];

    // Constants
    public const MIN_SUPPLIER_BALANCE = 500000; // 500,000 RUB guarantee

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    // Relations
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(TenderReview::class);
    }

    // Methods
    public function canBeWithdrawn(): bool
    {
        return $this->status === self::STATUS_SUBMITTED 
            && $this->tender->canBeParticipated();
    }

    public function freezeGuarantee(float $amount): void
    {
        $this->guarantee_amount = $amount;
        $this->guarantee_frozen_at = now();
        $this->save();
    }

    public function releaseGuarantee(): void
    {
        $this->guarantee_released_at = now();
        $this->save();
    }
}
    {
        $this->guarantee_released_at = now();
        $this->save();
    }
}
