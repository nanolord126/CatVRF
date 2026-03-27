<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Модель объекта недвижимости (Property)
 */
final class StrProperty extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'str_properties';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'address',
        'city',
        'lat',
        'lon',
        'type',
        'is_active',
        'is_verified',
        'rating',
        'review_count',
        'schedule_json',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'rating' => 'float',
        'schedule_json' => 'json',
        'tags' => 'json',
        'lat' => 'decimal:8',
        'lon' => 'decimal:8',
    ];

    /**
     * КАНОН 2026: Автоматическое назначение UUID и tenant scoping
     */
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

    public function apartments(): HasMany
    {
        return $this->hasMany(StrApartment::class, 'property_id');
    }
}
