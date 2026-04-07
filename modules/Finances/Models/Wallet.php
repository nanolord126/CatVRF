<?php

declare(strict_types=1);

namespace Modules\Finances\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Finances\Enums\WalletType;

final class Wallet extends Model
{
    use HasFactory;

    protected $table = 'wallets';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'type',
        'current_balance',
        'hold_amount',
        'correlation_id',
    ];

    protected $casts = [
        'type' => WalletType::class,
        'current_balance' => 'integer',
        'hold_amount' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class);
    }
}
