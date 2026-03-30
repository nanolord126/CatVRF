<?php declare(strict_types=1);

namespace App\Domains\HouseholdGoods\HomeAppliance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppliancePart extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
            static::creating(function (AppliancePart $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (int) (auth()->user()?->tenant_id ?? session('tenant_id', 1));
                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function ($builder) {
                $builder->where('tenant_id', auth()->user()?->tenant_id ?? session('tenant_id', 1));
            });
        }
}
