<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionCategory extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'fashion_categories';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'description',
            'slug',
            'image_url',
            'parent_category_id',
            'display_order',
            'is_active',
            'correlation_id',
        ];

        protected $casts = [
            'is_active' => 'boolean',
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
            return $this->belongsTo(self::class, 'parent_category_id');
        }

        public function children(): HasMany
        {
            return $this->hasMany(self::class, 'parent_category_id');
        }

        public function products(): HasMany
        {
            return $this->hasMany(FashionProduct::class, 'category_id');
        }
}
