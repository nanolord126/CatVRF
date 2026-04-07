<?php declare(strict_types=1);

namespace Modules\Wallet\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Wallet
 * @package Modules\Wallet\Models
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property int|null $user_id
 * @property int $owner_id
 * @property string $owner_type
 * @property int $current_balance
 * @property int $held_amount
 * @property string $currency
 * @property string|null $correlation_id
 * @property array|null $tags
 */
final class Wallet extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    /**
     * @var string
     */
    protected $table = 'wallets';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'user_id',
        'owner_id',
        'owner_type',
        'current_balance', // NOTE: Should be read-only and calculated from transactions. Direct modification is discouraged.
        'held_amount',
        'currency',
        'correlation_id',
        'tags',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'current_balance' => 'integer',
        'held_amount' => 'integer',
        'tags' => 'json',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Get the owner of the wallet (morphable relation).
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all wallet transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }

    /**
     * Get the available balance (current - held).
     */
    public function getAvailableBalanceAttribute(): int
    {
        return $this->current_balance - $this->held_amount;
    }

    /**
     * Get the balance usage percentage.
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->current_balance <= 0) {
            return 0.0;
        }

        return round(($this->held_amount / $this->current_balance) * 100.0, 2);
    }
}

