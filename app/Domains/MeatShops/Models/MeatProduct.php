<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MeatProduct extends Model
{
    use HasFactory;

    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'meat_products';
        protected $fillable = [
            'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
            'name', 'animal_type', 'cut_type', 'unit',
            'price_per_unit', 'current_stock',
            'is_farm_raised', 'is_halal', 'has_vet_certificate',
            'vet_certificate_num', 'is_vacuum_packed', 'status', 'tags',
        ];
        protected $casts = [
            'price_per_unit'       => 'int',
            'current_stock'        => 'int',
            'is_farm_raised'       => 'boolean',
            'is_halal'             => 'boolean',
            'has_vet_certificate'  => 'boolean',
            'is_vacuum_packed'     => 'boolean',
            'tags'                 => 'json',
        ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
