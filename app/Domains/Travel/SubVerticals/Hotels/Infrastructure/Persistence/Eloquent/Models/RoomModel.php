<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domains\Hotels\Domain\Entities\Room as RoomEntity;
use App\Domains\Hotels\Domain\ValueObjects\RoomId;
use App\Domains\Hotels\Domain\Enums\RoomType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class RoomModel extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $table = 'hotel_rooms';

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'hotel_id',
        'type',
        'price_per_night',
        'capacity',
        'amenities',
        'is_available',
    ];

    protected $casts = [
        'amenities' => 'json',
        'is_available' => 'boolean',
        'price_per_night' => 'integer',
        'capacity' => 'integer',
    ];

    public function hotel()
    {
        return $this->belongsTo(HotelModel::class, 'hotel_id');
    }

    public function bookings()
    {
        return $this->hasMany(BookingModel::class, 'room_id');
    }

    public function toDomainEntity(): RoomEntity
    {
        return new RoomEntity(
            id: RoomId::fromString($this->id),
            type: RoomType::from($this->type),
            pricePerNight: $this->price_per_night,
            capacity: $this->capacity,
            amenities: new Collection($this->amenities),
            isAvailable: $this->is_available
        );
    }


    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
