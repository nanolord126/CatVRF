<?php declare(strict_types=1);

namespace Modules\Wallet\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Payments\Enums\TransactionStatus;
use Modules\Wallet\Enums\TransactionType;

final class WalletTransaction extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'wallet_transactions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'wallet_id',
        'user_id',
        'type',
        'amount',
        'status',
        'currency',
        'source_type',
        'source_id',
        'correlation_id',
        'tags',
        'metadata',
        'description',
    ];

    protected $casts = [
        'amount' => 'integer',
        'tags' => AsCollection::class,
        'metadata' => 'json',
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function getAmountInRubles(): float
    {
        return $this->amount / 100;
    }

    public function setAmountInRubles(float $rubles): void
    {
        $this->amount = (int) ($rubles * 100);
    }
}
