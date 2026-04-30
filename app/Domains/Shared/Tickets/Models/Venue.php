<?php

declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Venue extends Model
{
    use TenantScoped;

    protected $table = 'venues';

    protected $fillable = [
        'uuid', 'tenant_id', 'business_group_id',
        'name', 'address', 'capacity', 'contacts',
        'is_active', 'tags', 'correlation_id',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'contacts' => 'json',
        'tags' => 'json',
    ];

    /**
     * Все эвенты площадки.
     */
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
    ) {}

    public function events(): HasMany
    {
        return $this->hasMany($this->eventDispatcher->class);
    }

    /**
     * Схемы залов площадки.
     */
    public function seatMaps(): HasMany
    {
        return $this->hasMany(SeatMap::class);
    }

    /**
     * Получить основные контактные данные.
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->contacts['phone'] ?? null;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->contacts['email'] ?? null;
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        self::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = tenant()?->id;
            }
        });
    }
}
