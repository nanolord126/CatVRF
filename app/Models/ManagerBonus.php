<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ManagerBonus extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'manager_id',
        'tender_id',
        'bid_id',
        'tenant_id',
        'vertical_id',
        'type',
        'sale_amount',
        'bonus_percent',
        'bonus_amount',
        'status',
        'approved_at',
        'paid_at',
        'paid_until',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'sale_amount' => 'decimal:2',
        'bonus_percent' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'paid_until' => 'datetime',
        'metadata' => 'array',
    ];

    // Constants
    public const SUPPLIER_BONUS_PERCENT = 1.5; // 1.5% for supplier sales

    public const TYPE_TENDER_WON = 'tender_won';
    public const TYPE_TENDER_CLOSED = 'tender_closed';
    public const TYPE_REFERRAL = 'referral';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    // Relations
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(TenderBid::class);
    }

    public function b2bOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\B2BOrder::class, 'tender_id'); // Using tender_id as reference_id for now
    }

    // Methods
    public function calculateBonusAmount(float $saleAmount, float $percent = null): float
    {
        $percent = $percent ?? self::SUPPLIER_BONUS_PERCENT;
        return $saleAmount * ($percent / 100);
    }

    public function approve(): void
    {
        $this->status = self::STATUS_APPROVED;
        $this->approved_at = now();
        $this->save();
    }

    public function markAsPaid(): void
    {
        $this->status = self::STATUS_PAID;
        $this->paid_at = now();
        $this->save();
    }

    public function cancel(string $reason = null): void
    {
        $this->status = self::STATUS_CANCELLED;
        if ($reason) {
            $this->notes = $reason;
        }
        $this->save();
    }

    /**
     * Scope for pending bonuses
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved bonuses
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for paid bonuses
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for overdue bonuses
     */
    public function scopeOverdue($query)
    {
        return $query->where('paid_until', '<', now())
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }
}
