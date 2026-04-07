<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domains\Hotels\Domain\Entities\Booking as BookingEntity;
use App\Domains\Hotels\Domain\ValueObjects\BookingId;
use App\Domains\Hotels\Domain\ValueObjects\HotelId;
use App\Domains\Hotels\Domain\ValueObjects\RoomId;
use App\Domains\Hotels\Domain\Enums\BookingStatus;
use Carbon\Carbon;

final class BookingModel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hotel_bookings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'hotel_id',
        'room_id',
        'user_id',
        'check_in_date',
        'check_out_date',
        'total_price',
        'status',
        'correlation_id',
    ];

    protected $casts = [
        'check_in_date' => 'datetime',
        'check_out_date' => 'datetime',
        'total_price' => 'integer',
    ];

    public function hotel()
    {
        return $this->belongsTo(HotelModel::class, 'hotel_id');
    }

    public function room()
    {
        return $this->belongsTo(RoomModel::class, 'room_id');
    }

    public function toDomainEntity(): BookingEntity
    {
        return new BookingEntity(
            id: BookingId::fromString($this->id),
            hotelId: HotelId::fromString($this->hotel_id),
            roomId: RoomId::fromString($this->room_id),
            userId: $this->user_id,
            checkInDate: Carbon::parse($this->check_in_date),
            checkOutDate: Carbon::parse($this->check_out_date),
            totalPrice: $this->total_price,
            status: BookingStatus::from($this->status),
            correlationId: $this->correlation_id
        );
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

}