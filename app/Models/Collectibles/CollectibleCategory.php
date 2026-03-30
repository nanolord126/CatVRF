<?php declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CollectibleCategory extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'collectible_categories';

        protected $fillable = [
            'name',
            'slug',
            'tenant_id',
        ];

        protected static function booted(): void
        {
            static::creating(function (CollectibleCategory $model) {
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', (tenant()->id ?? 1));
            });
        }

        /**
         * Get items under this category.
         */
        public function items(): HasMany
        {
            return $this->hasMany(CollectibleItem::class, 'category_id');
        }
}
