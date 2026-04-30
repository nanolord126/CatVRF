<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoyaltyTransactionModel extends Model
{
    use HasFactory;

    protected $table = 'beauty_loyalty_transactions';

    protected $fillable = [
        'loyalty_profile_id',
        'appointment_id',
        'type',
        'points',
        'balance_before',
        'balance_after',
        'description',
        'metadata',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
    ];

    public function loyaltyProfile(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProfileModel::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(AppointmentModel::class);
    }
}
