<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Bonus extends Model
{
    protected $table = 'bonuses';

    protected $fillable = [
        'user_id',
        'amount',
        'type', // referral, turnover, promo, loyalty
        'reason',
        'source_id',
        'source_type',
        'correlation_id',
        'uuid',
        'tags',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'tags' => 'json',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
