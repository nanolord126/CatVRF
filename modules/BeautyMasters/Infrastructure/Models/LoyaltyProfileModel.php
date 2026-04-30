<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LoyaltyProfileModel extends Model
{
    use HasFactory;

    protected $table = 'beauty_loyalty_profiles';

    protected $fillable = [
        'client_id',
        'venue_id',
        'points_balance',
        'points_earned',
        'points_redeemed',
        'tier',
        'total_spent',
        'total_visits',
        'tier_updated_at',
        'last_activity_at',
        'birthday_gift_sent_year',
        'preferences',
    ];

    protected $casts = [
        'points_balance' => 'integer',
        'points_earned' => 'integer',
        'points_redeemed' => 'integer',
        'total_spent' => 'decimal:2',
        'total_visits' => 'integer',
        'tier_updated_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'birthday_gift_sent_year' => 'integer',
        'preferences' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientModel::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransactionModel::class);
    }
}
