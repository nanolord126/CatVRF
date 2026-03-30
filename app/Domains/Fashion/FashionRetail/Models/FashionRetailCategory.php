<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionRetailCategory extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'fashion_retail_categories';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'description',
            'parent_id',
            'icon_url',
            'image_url',
            'order',
            'tags',
        ];

        protected $casts = [
            'tags' => 'json',
            'order' => 'integer',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function ($query) {
                if (tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }

        public function parent(): BelongsTo
        {
            return $this->belongsTo(FashionRetailCategory::class, 'parent_id');
        }

        public function children()
        {
            return $this->hasMany(FashionRetailCategory::class, 'parent_id');
        }

        public function products()
        {
            return $this->hasMany(FashionRetailProduct::class, 'category_id');
        }
}
