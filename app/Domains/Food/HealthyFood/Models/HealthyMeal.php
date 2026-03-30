<?php declare(strict_types=1);

namespace App\Domains\Food\HealthyFood\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HealthyMeal extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'healthy_meals';
        protected $fillable = [
            'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
            'name', 'description', 'diet_type', 'calories',
            'protein_g', 'fat_g', 'carbs_g', 'price',
            'prep_time_min', 'allergens', 'photo_url', 'status', 'tags',
        ];
        protected $casts = [
            'calories'      => 'int',
            'protein_g'     => 'int',
            'fat_g'         => 'int',
            'carbs_g'       => 'int',
            'price'         => 'int',
            'prep_time_min' => 'int',
            'allergens'     => 'array',
            'tags'          => 'json',
        ];

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }
}
