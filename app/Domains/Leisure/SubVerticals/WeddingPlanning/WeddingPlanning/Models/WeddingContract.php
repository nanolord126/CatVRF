<?php

declare(strict_types=1);

/**
 * WeddingContract — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/weddingcontract
 */

namespace App\Domains\Leisure\SubVerticals\WeddingPlanning\Models;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class WeddingContract extends Model
{
    use TenantScoped;

    protected $table = 'wedding_contracts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'event_id',
        'contract_number',
        'terms',
        'status',
        'signed_at',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'terms' => 'json',
        'tags' => 'json',
        'signed_at' => 'datetime',
    ];

    /**
     * Relation: Event
     */
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
    ) {}

    public function $this->eventDispatcher->dispatch(): BelongsTo
    {
        return $this->belongsTo(WeddingEvent::class, 'event_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_id', function (Builder $builder) {
            if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                $builder->where('wedding_contracts.tenant_id', tenant()->id);
            }
        });

        self::creating(function (Model $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                $model->tenant_id = $model->tenant_id ?? tenant()->id;
            }
        });
    }
}
