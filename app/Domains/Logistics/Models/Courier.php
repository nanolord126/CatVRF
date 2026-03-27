<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * Courier Model (2026 Edition)
 * 
 * Описывает курьера платформы CatVRF.
 * Канон 2026: UUID, Tenant Scoping, JSONB, Correlation_id, PostGIS.
 */
final class Courier extends Model
{
    use SoftDeletes;

    protected $table = 'couriers';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'vehicle_id',
        'status',
        'current_location',
        'rating',
        'commission_percent',
        'tags',
        'correlation_id'
    ];

    protected $hidden = [
        'password',
        'token',
        'secret',
    ];

    protected $casts = [
        'uuid' => 'string',
        'status' => 'string',
        'rating' => 'float',
        'commission_percent' => 'integer',
        'tags' => 'json',
        'current_location' => 'object', 
    ];

    /**
     * Is courier online and ready for new tasks.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'online' && !$this->deleted_at;
    }

    /**
     * Get current status color for UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'online' => 'success',
            'busy' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Глобальная изоляция по tenant_id
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->tenant_id) && function_exists('filament') && filament()->getTenant()) {
                $model->tenant_id = filament()->getTenant()->id;
            }
        });

        static::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('filament') && filament()->getTenant()) {
                $query->where('tenant_id', filament()->getTenant()->id);
            }
        });
    }

    // --- RELATIONS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'courier_id');
    }

    /**
     * Related routes for the courier via orders.
     */
    public function routes(): HasMany
    {
        return $this->hasMany(Route::class, 'courier_id');
    }
}

