<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Cart extends Model
{
    protected $table = 'carts';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'seller_id',
        'uuid',
        'correlation_id',
        'status',
        'reserved_until',
        'tags',
    ];

    protected $casts = [
        'tags'           => 'json',
        'reserved_until' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function isExpired(): bool
    {
        return $this->reserved_until !== null && $this->reserved_until->isPast();
    }
}
