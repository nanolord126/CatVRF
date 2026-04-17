<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Infrastructure\Eloquent\Models;

use App\Domains\RealEstate\Domain\Enums\PropertyStatusEnum;
use App\Domains\RealEstate\Domain\Enums\PropertyTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string              $id
 * @property int                 $tenant_id
 * @property string              $agent_id
 * @property string              $title
 * @property string              $description
 * @property string              $address
 * @property float               $latitude
 * @property float               $longitude
 * @property string              $type
 * @property int                 $price_kopecks
 * @property float               $area_sqm
 * @property int                 $rooms
 * @property int                 $floor
 * @property int                 $total_floors
 * @property string              $status
 * @property string|null         $correlation_id
 * @property array|null          $tags
 */
final class PropertyModel extends Model
{

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'real_estate_properties';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'id',
        'tenant_id',
        'agent_id',
        'title',
        'description',
        'address',
        'latitude',
        'longitude',
        'type',
        'price_kopecks',
        'area_sqm',
        'rooms',
        'floor',
        'total_floors',
        'status',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'price_kopecks' => 'integer',
        'area_sqm'      => 'float',
        'rooms'         => 'integer',
        'floor'         => 'integer',
        'total_floors'  => 'integer',
        'latitude'      => 'float',
        'longitude'     => 'float',
        'tags'          => 'array',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AgentModel::class, 'agent_id', 'id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PropertyPhotoModel::class, 'property_id', 'id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PropertyDocumentModel::class, 'property_id', 'id');
    }

    public function viewings(): HasMany
    {
        return $this->hasMany(ViewingAppointmentModel::class, 'property_id', 'id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(ContractModel::class, 'property_id', 'id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($builder): void {
            if (function_exists('tenant') && tenant() !== null) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }
}
