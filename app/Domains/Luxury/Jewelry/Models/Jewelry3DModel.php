<?php

declare(strict_types=1);


namespace App\Domains\Luxury\Jewelry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final /**
 * Jewelry3DModel
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Jewelry3DModel extends Model
{
    protected $table = '3d_models';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'jewelry_item_id',
        'model_url',
        'texture_url',
        'material_type',
        'dimensions',
        'weight_grams',
        'preview_image_url',
        'ar_compatible',
        'vr_compatible',
        'file_size_mb',
        'format',
        'status',
        'tags',
    ];

    protected $casts = [
        'dimensions' => 'json',
        'tags' => 'json',
        'ar_compatible' => 'boolean',
        'vr_compatible' => 'boolean',
    ];

    public function jewelry(): BelongsTo
    {
        return $this->belongsTo(JewelryItem::class, 'jewelry_item_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', filament()->getTenant()->id);
        });
    }
}
