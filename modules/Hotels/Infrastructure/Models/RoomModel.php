<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Models;

use App\Models\BaseDomainModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Media\Domain\Traits\HasMediaTrait;

final class RoomModel extends BaseDomainModel
{
    use HasFactory, SoftDeletes;
    use HasMediaTrait;

    protected $table = 'hotels_rooms';

    protected $fillable = [
        'tenant_id',
        'venue_id',
        'room_type_id',
        'uuid',
        'room_number',
        'external_code',
        'floor',
        'building',
        'wing',
        'status',
        'clean_status',
        'features',
        'photos',
        'is_accessible',
        'has_view',
        'connectable',
        'connected_rooms',
        'housekeeping_notes',
        'last_cleaned_at',
        'last_cleaned_by',
        'is_active',
    ];

    protected $casts = [
        'status' => 'string',
        'clean_status' => 'string',
        'features' => 'array',
        'photos' => 'array',
        'is_accessible' => 'boolean',
        'has_view' => 'boolean',
        'connectable' => 'boolean',
        'connected_rooms' => 'array',
        'last_cleaned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomTypeModel::class, 'room_type_id');
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItemModel::class, 'room_id');
    }

    public function housekeepingTasks(): HasMany
    {
        return $this->hasMany(HousekeepingTaskModel::class, 'room_id');
    }
}
