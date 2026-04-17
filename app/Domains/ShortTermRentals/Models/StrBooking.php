<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class StrBooking extends Model
{

    protected $table = 'str_bookings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'apartment_id',
        'user_id',
        'check_in',
        'check_out',
        'status',
        'total_price',
        'deposit_amount',
        'deposit_status',
        'payment_status',
        'payout_at',
        'is_b2b',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'payout_at' => 'datetime',
        'total_price' => 'integer',
        'deposit_amount' => 'integer',
        'is_b2b' => 'boolean',
        'metadata' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(StrApartment::class, 'apartment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(StrReview::class, 'booking_id');
    }

    /**
     * Проверка: залог вхолдирован
     */
    public function isDepositHeld(): bool
    {
        return $this->deposit_status === 'held';
    }

    /**
     * Проверка: проживание завершено и готово к выплате
     */
    public function isReadyForPayout(): bool
    {
        return $this->status === 'completed' && $this->payout_at && $this->payout_at->isPast();
    }
}
