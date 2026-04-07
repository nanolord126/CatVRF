<?php

declare(strict_types=1);

namespace Modules\Finances\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finances\Enums\PaymentStatus;

final class PaymentTransaction extends Model
{
    use HasFactory;

    protected $table = 'payment_transactions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'idempotency_key',
        'provider_code',
        'provider_payment_id',
        'status',
        'amount',
        'hold',
        'captured_at',
        'refunded_at',
        'correlation_id',
        'meta',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
        'amount' => 'integer',
        'hold' => 'boolean',
        'captured_at' => 'datetime',
        'refunded_at' => 'datetime',
        'meta' => 'json',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
