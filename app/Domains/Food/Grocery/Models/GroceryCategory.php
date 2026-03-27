<?php

declare(strict_types=1);


namespace App\Domains\Food\Grocery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final /**
 * GroceryCategory
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class GroceryCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'name', 'parent_id', 'icon',
        'uuid', 'correlation_id', 'tags',
    ];

    protected $casts = ['tags' => 'json'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) =>
            $q->where('tenant_id', tenant()->id ?? 0)
        );
    }

    public function products(): HasMany
    {
        return $this->hasMany(GroceryProduct::class, 'category_id');
    }
}
