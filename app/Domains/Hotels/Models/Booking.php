<?php declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class Booking extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'hotel_id',
        'room_type_id',
        'guest_id',
        'confirmation_code',
        'check_in_date',
        'check_out_date',
        'number_of_guests',
        'nights_count',
        'subtotal_price',
        'cleaning_fee',
        'commission_price',
        'total_price',
        'payment_status',
        'booking_status',
        'special_requests',
        'paid_at',
        'checked_in_at',
        'checked_out_at',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'check_in_date' => 'datetime',
        'check_out_date' => 'datetime',
        'paid_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'tags' => 'collection',
    ];

    public function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant('id') ?? 0));
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function review(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
