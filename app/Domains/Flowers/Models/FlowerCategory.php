declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * FlowerCategory
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FlowerCategory extends Model
{
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
