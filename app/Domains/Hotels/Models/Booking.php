<?php declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Booking Model (Layer 2)
 * 
 * Бронирование отеля.
 * Поля mandatory: uuid, tenant_id, correlation_id, business_group_id.
 */
final class Booking extends Model
{
    use SoftDeletes;

    protected $table = 'hotel_bookings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'hotel_id',
        'room_id',
        'user_id',
        'check_in',
        'check_out',
        'status', // pending, confirmed, active, completed, cancelled, failed
        'total_price',
        'currency',
        'payment_status', // pending, paid, refund_pending, refunded
        'payout_at',
        'is_b2b',
        'contract_id',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'total_price' => 'integer',
        'is_b2b' => 'boolean',
        'metadata' => 'json',
        'payout_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (int) tenant('id');
            $model->business_group_id = $model->business_group_id ?? 1; // Default
        });

        static::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (int) tenant('id'));
        });
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Возвращает true, если бронирование оплачено.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Рассчитать количество ночей
     */
    public function nights(): int
    {
        return (int) $this->check_in->diffInDays($this->check_out);
    }
}
