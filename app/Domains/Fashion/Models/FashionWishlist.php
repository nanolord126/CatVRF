<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FashionWishlist extends Model
{
    use SoftDeletes;

    protected $table = 'fashion_wishlists';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'product_id',
        'color',
        'size',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'tags' => 'collection',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function ($query) {
            if (tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }
}
