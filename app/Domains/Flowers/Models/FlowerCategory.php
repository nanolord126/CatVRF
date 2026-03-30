<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerCategory extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'flower_categories';

        protected $fillable = [
            'tenant_id',
            'uuid',
            'name',
            'slug',
            'description',
            'correlation_id',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (auth()->check() && tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function bouquets(): HasMany
        {
            return $this->hasMany(Bouquet::class, 'category_id');
        }
}
