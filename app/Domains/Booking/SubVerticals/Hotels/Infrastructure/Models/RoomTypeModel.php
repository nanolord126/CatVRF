<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Models;

use App\Models\BaseDomainModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Media\Domain\Traits\HasMediaTrait;

final class RoomTypeModel extends BaseDomainModel
{
    use HasFactory, SoftDeletes;
    use HasMediaTrait;

    protected $table = 'hotels_room_types';

    protected $fillable = [
        'tenant_id',
        'venue_id',
        'uuid',
        'name',
        'code',
        'description',
        'max_occupancy',
        'max_adults',
        'max_children',
        'number_of_beds',
        'bed_configuration',
        'area_sqm',
        'amenities',
        'photos',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'max_occupancy' => 'integer',
        'max_adults' => 'integer',
        'max_children' => 'integer',
        'number_of_beds' => 'integer',
        'area_sqm' => 'decimal:2',
        'amenities' => 'array',
        'photos' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(RoomModel::class, 'room_type_id');
    }
}
