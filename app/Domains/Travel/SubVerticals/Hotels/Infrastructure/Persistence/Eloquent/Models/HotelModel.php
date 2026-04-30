<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domains\Hotels\Domain\Entities\Hotel as HotelEntity;
use App\Domains\Hotels\Domain\ValueObjects\HotelId;
use App\Domains\Hotels\Domain\ValueObjects\Address;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class HotelModel extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $table = 'hotels';

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'name',
        'description',
        'address',
        'amenities',
        'rating',
        'correlation_id',
    ];

    protected $casts = [
        'address' => 'json',
        'amenities' => 'json',
        'rating' => 'float',
    ];

    public function rooms()
    {
        return $this->hasMany(RoomModel::class, 'hotel_id');
    }

    public function toDomainEntity(): HotelEntity
    {
        return new HotelEntity(
            id: HotelId::fromString($this->id),
            tenantId: $this->tenant_id,
            name: $this->name,
            address: new Address(
                country: $this->address['country'],
                city: $this->address['city'],
                street: $this->address['street'],
                houseNumber: $this->address['house_number'],
                zipCode: $this->address['zip_code'] ?? null
            ),
            description: $this->description,
            rooms: new Collection($this->rooms->map(fn (RoomModel $room) => $room->toDomainEntity())),
            amenities: new Collection($this->amenities),
            rating: $this->rating,
            correlationId: $this->correlation_id
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
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
