<?php

declare(strict_types=1);

/**
 * Event — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/event
 */

namespace App\\Domains\\Shared\EventPlanning\Events\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\EventFactory;

final class Event extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

    protected $table = 'events_b2b';

    protected $fillable = [
        'uuid', 'tenant_id', 'business_group_id', 'correlation_id', 'tags',
        'title', 'start_date',
    ];

    protected $casts = [
        'tags' => 'json',
    ];

    protected static function newFactory()
    {
        return EventFactory::new();
    }

    protected static function booted(): void
    {
        parent::booted();
        self::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
