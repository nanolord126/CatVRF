<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class TaxiClientFavorite extends Model
{
    use HasFactory;

    protected $table = 'taxi_client_favorites';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'type',
        'name',
        'address',
        'latitude',
        'longitude',
        'driver_id',
        'is_default',
        'correlation_id',
        'metadata',
        'tags'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_default' => 'boolean',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    protected $hidden = ['metadata'];

    /**
     * Типы избранных.
     */
    public const TYPE_LOCATION = 'location';
    public const TYPE_DRIVER = 'driver';

    protected static function booted(): void
    {
        static::creating(function (TaxiClientFavorite $favorite) {
            $favorite->uuid = $favorite->uuid ?? (string) Str::uuid();
            $favorite->tenant_id = $favorite->tenant_id ?? (tenant()->id ?? 1);
            $favorite->is_default = $favorite->is_default ?? false;
            $favorite->correlation_id = $favorite->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Отношения.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    /**
     * Проверить, является ли избранное по умолчанию.
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Пометить как избранное по умолчанию.
     */
    public function markAsDefault(): void
    {
        // Remove default from other favorites of same type for this user
        static::where('user_id', $this->user_id)
            ->where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
