<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Models;

use App\Models\BaseDomainModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class GuestModel extends BaseDomainModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'hotels_guests';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'uuid',
        'first_name',
        'last_name',
        'patronymic',
        'email',
        'phone',
        'passport_number',
        'passport_expiry',
        'nationality',
        'date_of_birth',
        'gender',
        'preferences',
        'vip_status',
        'loyalty_points',
        'loyalty_tier_id',
        'total_stays',
        'total_nights',
        'total_spent',
        'is_blacklisted',
        'blacklist_reason',
        'notes',
        'first_stay_at',
        'last_stay_at',
    ];

    protected $casts = [
        'passport_expiry' => 'date',
        'date_of_birth' => 'date',
        'preferences' => 'array',
        'vip_status' => 'array',
        'loyalty_points' => 'integer',
        'total_stays' => 'integer',
        'total_nights' => 'integer',
        'total_spent' => 'decimal:2',
        'is_blacklisted' => 'boolean',
        'notes' => 'array',
        'first_stay_at' => 'datetime',
        'last_stay_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(BookingModel::class, 'guest_id');
    }
}
