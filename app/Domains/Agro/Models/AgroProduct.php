<?php

declare(strict_types=1);

namespace App\Domains\Agro\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Продукт агропромышленного комплекса (Мясо, Молоко, Зерно и т.д.)
 */
final class AgroProduct extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $table = 'agro_products';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'farm_id',
        'name',
        'sku',
        'category',      // meat, dairy, produce, grain
        'price_cents',
        'unit',          // kg, liter, ton
        'current_stock',
        'min_stock_alert',
        'properties',    // fat_percent, organic, certified
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'current_stock' => 'float',
        'properties' => 'json',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());
        });
    }

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(AgroFarm::class, 'farm_id');
    }
}
