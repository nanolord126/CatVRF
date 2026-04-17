<?php declare(strict_types=1);

namespace Modules\Restaurant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'restaurant_reviews';

    protected $fillable = [
        'tenant_id',
        'restaurant_id',
        'user_id',
        'uuid',
        'rating',
        'comment',
        'images',
        'is_verified',
        'helpful_count',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified' => 'boolean',
        'helpful_count' => 'integer',
        'images' => 'json',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Global scope для tenant scoping.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    /**
     * Получить ресторан.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    /**
     * Получить пользователя.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
