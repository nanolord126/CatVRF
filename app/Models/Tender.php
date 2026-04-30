<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Tender extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'manager_id',
        'tenant_id',
        'vertical_id',
        'title',
        'description',
        'type',
        'min_amount',
        'duration_months',
        'starts_at',
        'ends_at',
        'delivery_start_date',
        'delivery_end_date',
        'status',
        'winning_bid_id',
        'requirements',
        'delivery_terms',
        'payment_terms',
        'requires_platform_guarantee',
        'guarantee_fee_amount',
        'guarantee_paid_at',
        'hold_amount',
        'hold_frozen_at',
        'hold_release_date',
        'hold_released_at',
        'credit_score',
        'credit_risk_level',
        'credit_assessed_at',
        'fraud_check_passed',
        'fraud_check_notes',
        'documents_signed',
        'documents_signed_at',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'delivery_start_date' => 'datetime',
        'delivery_end_date' => 'datetime',
        'requirements' => 'array',
        'delivery_terms' => 'array',
        'payment_terms' => 'array',
    ];

    // Constants
    public const MIN_AMOUNT = 100000; // 100,000 RUB
    public const MIN_DURATION_MONTHS = 3; // 3 months for supply contracts
    public const PLATFORM_COMMISSION_PERCENT = 7.0; // 7% commission for tenders
    public const PLATFORM_GUARANTEE_FEE_PERCENT = 5.2; // 5.2% fee for platform guarantee
    public const HOLD_DAYS_BEFORE_DELIVERY = 4; // Hold funds 4 days before delivery

    public const TYPE_SUPPLY = 'supply';
    public const TYPE_ONE_TIME = 'one_time';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    // Relations
    public function bids(): HasMany
    {
        return $this->hasMany(TenderBid::class);
    }

    public function winningBid(): BelongsTo
    {
        return $this->belongsTo(TenderBid::class, 'winning_bid_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TenderReview::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Validation methods
    public function validateTenderRules(): array
    {
        $errors = [];

        // Validate minimum amount
        if ($this->min_amount < self::MIN_AMOUNT) {
            $errors[] = "Minimum tender amount must be at least " . number_format(self::MIN_AMOUNT, 2) . " RUB";
        }

        // Validate duration for supply contracts
        if ($this->type === self::TYPE_SUPPLY && $this->duration_months < self::MIN_DURATION_MONTHS) {
            $errors[] = "Supply contracts must have a minimum duration of " . self::MIN_DURATION_MONTHS . " months";
        }

        // Validate dates
        if ($this->starts_at && $this->ends_at && $this->starts_at->gte($this->ends_at)) {
            $errors[] = "End date must be after start date";
        }

        return $errors;
    }

    public function canBeParticipated(): bool
    {
        return $this->status === self::STATUS_ACTIVE 
            && $this->starts_at->lte(now()) 
            && $this->ends_at->gt(now());
    }

    public function getSuccessRate(): float
    {
        $totalBids = $this->bids()->count();
        if ($totalBids === 0) {
            return 0;
        }

        return ($this->bids()->where('status', TenderBid::STATUS_ACCEPTED)->count() / $totalBids) * 100;

    /**
     * Calculate platform commission for tender
     */
    public function calculateCommission(float $amount): float
    {
        return $amount * (self::PLATFORM_COMMISSION_PERCENT / 100);
    }

    /**
     * Get total amount with commission
     */
    public function getTotalWithCommission(float $amount): array
    {
        $commission = $this->calculateCommission($amount);
        return [
            'amount' => $amount,
            'commission_percent' => self::PLATFORM_COMMISSION_PERCENT,
            'commission_amount' => $commission,
            'total' => $amount + $commission,
        ];
    }
    }
}
