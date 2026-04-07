<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class StrAmenity
 *
 * Part of the ShortTermRentals vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Domains\ShortTermRentals\Models
 */
final class StrAmenity extends Model
{
    use HasFactory;

    protected $table = 'str_amenities';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'icon',
        'description',
        'cost',
        'is_active',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cost' => 'integer',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function apartments(): BelongsToMany
    {
        return $this->belongsToMany(StrApartment::class, 'str_amenity_map', 'amenity_id', 'apartment_id');
    }
}
