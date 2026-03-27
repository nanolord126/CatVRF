<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Модель дополнительных услуг (Amenity)
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
        static::creating(function (self $model) {
            $model->uuid ??= (string) Str::uuid();
            $model->correlation_id ??= request()->header('X-Correlation-ID', (string) Str::uuid());
            $model->tenant_id ??= tenant()->id ?? null;
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function apartments(): BelongsToMany
    {
        return $this->belongsToMany(StrApartment::class, 'str_amenity_map', 'amenity_id', 'apartment_id');
    }
}
