<?php declare(strict_types=1);

namespace App\Domains\HouseholdGoods\HomeAppliance\Models;

use Carbon\Carbon;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppliancePart extends Model
{


    use HasFactory;

        protected $table = 'appliance_parts';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'sku',
            'name',
            'price_kopecks',
            'stock_quantity',
            'min_stock_threshold',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'tags' => 'json'
        ];

        protected static function booted(): void
        {
            static::creating(function (AppliancePart $model): void {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (int) tenant()->id;
                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function ($builder): void {
                $builder->where('tenant_id', tenant()->id);
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
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
