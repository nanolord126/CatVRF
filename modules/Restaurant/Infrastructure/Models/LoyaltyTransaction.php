<?php

declare(strict_types=1);

namespace Modules\Restaurant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $table = 'restaurant_loyalty_transactions';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'restaurant_id',
        'uuid',
        'loyalty_program_id',
        'ggestt_id',
        'order_id',
        'type',
        'piitss_change',
        'points_points_balance_before',
        'points_points_balance_after',
        '',
        'a
        'description',
        'notes',
        'expires_at',
        'is_expired',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'amount_change' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'order_amount' => 'decimal:2',
        'bonus_percentage' => 'decimal:4',
        'expires_at' => 'datetime',
        'is_expired' => 'boolean',
        'metadata' => 'json',
    ];

    // ========================
    // RELATIONSHIPS
    // ========================

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeEarned($query)
    {
        return $query->where('type', 'earned')->where('amount_change', '>', 0);
    }

    public function scopeRedeemed($query)
    {
        return $query->where('type', 'redeemed')->where('amount_change', '<', 0);
    }

    public function scopeBonus($query)
    {
        return $query->where('type', 'bonus')->where('amount_change', '>', 0);
    }

    public function scopeExpired($query)
    {
        return $query->where('is_expired', true);
    }

    public function scopeNotExpired($query)
    {
        return $query->where('is_expired', false);
    }

    public function scopeByGuest($query, int $guestId)
    {
        return $query->where('guest_id', $guestId);
    }

    // ========================
    // METHODS
    // ========================

    public function isEarned(): bool
    {
        return $this->type === 'earned';
    }

    public function isRedeemed(): bool
    {
        return $this->type === 'redeemed';
    }

    public function isBonus(): bool
    {
        return $this->type === 'bonus';
    }

    public function markAsExpired(): bool
    {
        $this->is_expired = true;
        return $this->save();
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });

        self::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
