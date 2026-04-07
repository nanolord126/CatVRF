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
 * GardenPlant Model — Биологические свойства растений.
 *
 * @package CatVRF Gardening Vertical
 */
final class GardenPlant extends Model
{
    use HasFactory;

    protected $table = 'garden_plants';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'product_id',
        'botanical_name',
        'hardiness_zone',
        'light_requirement',
        'water_needs',
        'care_calendar',
        'is_seedling',
        'sowing_start',
        'harvest_start',
    ];

    protected $casts = [
        'care_calendar' => 'json',
        'is_seedling' => 'boolean',
        'sowing_start' => 'date',
        'harvest_start' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $query->where('garden_plants.tenant_id', tenant()->id);
        });

        static::creating(function (Model $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(GardenProduct::class, 'product_id');
    }
}
