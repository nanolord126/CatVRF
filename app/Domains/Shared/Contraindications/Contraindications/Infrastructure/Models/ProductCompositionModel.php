<?php

declare(strict_types=1);

namespace Modules\Contraindications\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class ProductCompositionModel extends Model
{
    use HasFactory;

    protected $table = 'product_compositions';

    protected $fillable = [
        'tenant_id',
        'composable_type',
        'composable_id',
        'ingredients',
        'calories_per_100g',
        'proteins',
        'fats',
        'carbs',
        'allergens',
    ];

    protected $casts = [
        'ingredients' => 'array',
        'allergens' => 'array',
        'calories_per_100g' => 'decimal:2',
        'proteins' => 'decimal:2',
        'fats' => 'decimal:2',
        'carbs' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.tenant_model'));
    }

    public function composable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
