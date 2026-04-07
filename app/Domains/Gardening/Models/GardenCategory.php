<?php
declare(strict_types=1);

namespace App\Domains\Gardening\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * GardenStore Model — Садовые магазины.
 *
 * @package CatVRF Gardening Vertical
 */
/**
 * GardenCategory Model — Категории садовых товаров.
 *
 * @package CatVRF Gardening Vertical
 */
final class GardenCategory extends Model
{
    use HasFactory;

    protected $table = 'garden_categories';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'name',
        'slug',
        'care_guide_summary',
    ];

    protected $casts = [];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $query->where('garden_categories.tenant_id', tenant()->id);
        });

        static::creating(function (Model $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(GardenProduct::class, 'category_id');
    }
}
