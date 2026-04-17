<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionUserMemoryInteraction extends Model
{
    protected $table = 'fashion_user_memory_interactions';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id',
        'user_id',
        'tenant_id',
        'product_id',
        'interaction_type',
        'interaction_score',
        'category',
        'brand',
        'price',
        'style_profile',
        'color',
        'context',
        'correlation_id',
    ];
    protected $casts = [
        'interaction_score' => 'decimal:2',
        'price' => 'integer',
        'context' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }
}
