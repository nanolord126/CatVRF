<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Media\Domain\Traits\HasMediaTrait;

final class VenueModel extends Model
{
    use HasFactory, SoftDeletes;
    use HasMediaTrait;

    protected $table = 'beauty_venues';

    protected $fillable = [
        'business_group_id',
        'user_id',
        'name',
        'slug',
        'description',
        'address',
        'city',
        'phone',
        'email',
        'website',
        'working_hours',
        'latitude',
        'longitude',
        'is_active',
        'is_chain',
        'parent_venue_id',
        'amenities',
        'social_media',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'is_chain' => 'boolean',
        'amenities' => 'array',
        'social_media' => 'array',
    ];

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'parent_venue_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(VenueModel::class, 'parent_venue_id');
    }

    public function masters(): HasMany
    {
        return $this->hasMany(MasterModel::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ServiceModel::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(ClientModel::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(AppointmentModel::class);
    }
}
