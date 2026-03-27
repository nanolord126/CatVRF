<?php

declare(strict_types=1);


namespace App\Domains\Fashion\FashionRetail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * FashionRetailCategory
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FashionRetailCategory extends Model
{
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
