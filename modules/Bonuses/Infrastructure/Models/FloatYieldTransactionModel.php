<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Bonuses\Domain\Entities\FloatYield as DomainEntity;

/**
 * Eloquent Model: FloatYieldTransactionModel
 *
 * Infrastructure model for persisting FloatYield domain entities to the database.
 * Maps to the float_yield_transactions table.
 *
 * Table schema:
 * - id: UUID primary key
 * - user_id: Foreign key to users table
 * - bonus_wallet_id: Foreign key to bonus_wallets table
 * - tenant_id: Foreign key to tenants table (nullable)
 * - yield_date: Date of yield calculation (YYYY-MM-DD)
 * - locked_balance_start: Locked balance at start of day (kopecks)
 * - locked_balance_end: Locked balance at end of day (kopecks)
 * - average_locked_balance: Average locked balance for the day (kopecks)
 * - platform_yield_rate: Platform annual yield rate (percentage)
 * - user_yield_rate: User annual yield rate (percentage)
 * - platform_yield: Platform yield amount (kopecks)
 * - user_yield: User yield amount (kopecks)
 * - partner_name: Partner name (if applicable)
 * - partner_transaction_id: Partner transaction ID for reconciliation
 * - partner_commission: Partner commission (kopecks)
 * - net_platform_revenue: Net platform revenue after commission (kopecks)
 * - correlation_id: Correlation ID for distributed tracing
 * - metadata: Additional metadata (JSON)
 * - created_at, updated_at: Timestamps
 *
 * Relationships:
 * - user: BelongsTo User model
 * - bonusWallet: BelongsTo BonusWallet model
 * - tenant: BelongsTo Tenant model (nullable)
 *
 * Domain mapping:
 * - toDomainEntity(): Converts model to domain entity
 * - fromDomainEntity(): Creates model from domain entity
 *
 * @see Modules\Bonuses\Domain\Entities\FloatYield
 */
class FloatYieldTransactionModel extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'float_yield_transactions';

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'user_id',
        'bonus_wallet_id',
        'tenant_id',
        'yield_date',
        'locked_balance_start',
        'locked_balance_end',
        'average_locked_balance',
        'platform_yield_rate',
        'user_yield_rate',
        'platform_yield',
        'user_yield',
        'partner_name',
        'partner_transaction_id',
        'partner_commission',
        'net_platform_revenue',
        'correlation_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'locked_balance_start' => 'integer',
        'locked_balance_end' => 'integer',
        'average_locked_balance' => 'integer',
        'platform_yield_rate' => 'float',
        'user_yield_rate' => 'float',
        'platform_yield' => 'integer',
        'user_yield' => 'integer',
        'partner_commission' => 'integer',
        'net_platform_revenue' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * Relationship to bonus wallet.
     */
    public function bonusWallet(): BelongsTo
    {
        return $this->belongsTo(\Modules\Bonuses\Infrastructure\Models\BonusWalletModel::class, 'bonus_wallet_id');
    }

    /**
     * Relationship to tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.models.tenant'), 'tenant_id');
    }

    /**
     * Converts model to domain entity.
     */
    public function toDomainEntity(): DomainEntity
    {
        return DomainEntity::fromArray([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'bonus_wallet_id' => $this->bonus_wallet_id,
            'tenant_id' => $this->tenant_id,
            'yield_date' => $this->yield_date,
            'locked_balance_start' => $this->locked_balance_start,
            'locked_balance_end' => $this->locked_balance_end,
            'average_locked_balance' => $this->average_locked_balance,
            'platform_yield_rate' => $this->platform_yield_rate,
            'user_yield_rate' => $this->user_yield_rate,
            'platform_yield' => $this->platform_yield,
            'user_yield' => $this->user_yield,
            'partner_name' => $this->partner_name,
            'partner_transaction_id' => $this->partner_transaction_id,
            'partner_commission' => $this->partner_commission,
            'net_platform_revenue' => $this->net_platform_revenue,
            'correlation_id' => $this->correlation_id,
            'metadata' => $this->metadata,
        ]);
    }

    /**
     * Creates model from domain entity.
     */
    public static function fromDomainEntity(DomainEntity $entity): self
    {
        return self::updateOrCreate(
            ['id' => $entity->id],
            [
                'user_id' => $entity->userId,
                'bonus_wallet_id' => $entity->bonusWalletId,
                'tenant_id' => $entity->tenantId,
                'yield_date' => $entity->yieldDate,
                'locked_balance_start' => $entity->lockedBalanceStart,
                'locked_balance_end' => $entity->lockedBalanceEnd,
                'average_locked_balance' => $entity->averageLockedBalance,
                'platform_yield_rate' => $entity->platformYieldRate,
                'user_yield_rate' => $entity->userYieldRate,
                'platform_yield' => $entity->platformYield,
                'user_yield' => $entity->userYield,
                'partner_name' => $entity->partnerName,
                'partner_transaction_id' => $entity->partnerTransactionId,
                'partner_commission' => $entity->partnerCommission,
                'net_platform_revenue' => $entity->netPlatformRevenue,
                'correlation_id' => $entity->correlationId,
                'metadata' => $entity->metadata,
            ]
        );
    }

    /**
     * Scope for user ID.
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for tenant ID.
     */
    public function scopeForTenant($query, ?string $tenantId)
    {
        if ($tenantId) {
            return $query->where('tenant_id', $tenantId);
        }
        return $query;
    }

    /**
     * Scope for yield date.
     */
    public function scopeForDate($query, string $date)
    {
        return $query->where('yield_date', $date);
    }

    /**
     * Scope for date range.
     */
    public function scopeForDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('yield_date', [$startDate, $endDate]);
    }

    /**
     * Scope for partner-backed yields.
     */
    public function scopeWithPartner($query)
    {
        return $query->whereNotNull('partner_name');
    }

    /**
     * Scope for specific partner.
     */
    public function scopeForPartner($query, string $partnerName)
    {
        return $query->where('partner_name', $partnerName);
    }

    /**
     * Gets total platform yield for a date range.
     */
    public function scopeTotalPlatformYield($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('yield_date', [$startDate, $endDate])
            ->sum('platform_yield');
    }

    /**
     * Gets total user yield for a date range.
     */
    public function scopeTotalUserYield($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('yield_date', [$startDate, $endDate])
            ->sum('user_yield');
    }
}
