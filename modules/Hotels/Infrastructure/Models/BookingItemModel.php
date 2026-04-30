<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Models;

use App\Models\BaseDomainModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BookingItemModel extends BaseDomainModel
{
    use HasFactory;

    protected $table = 'hotels_booking_items';

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'room_id',
        'room_type_id',
        'uuid',
        'check_in_date',
        'check_out_date',
        'nights',
        'room_rate',
        'total_amount',
        'guests',
        'is_active',
    ];

    protected $casts = [
        'check_in_date' => 'datetime',
        'check_out_date' => 'datetime',
        'nights' => 'integer',
        'room_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'guests' => 'array',
        'is_active' => 'boolean',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(RoomModel::class, 'room_id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomTypeModel::class, 'room_type_id');
    }

    public function housekeepingTasks(): HasMany
    {
        return $this->hasMany(HousekeepingTaskModel::class, 'booking_item_id');
    }
}
