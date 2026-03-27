<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * КАНЬОН 2026 — РАЗМЕР ИЗДЕЛИЯ
 * 
 * Содержит замеры (measurements) для SizeRecommendationService.
 */
final class FashionSize extends Model
{
    protected $table = 'fashion_sizes';

    protected $fillable = [
        'fashion_product_id',
        'size_type',
        'size_value',
        'stock',
        'measurements',
        'correlation_id',
    ];

    protected $casts = [
        'measurements' => 'json',
        'stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'fashion_product_id');
    }
}
