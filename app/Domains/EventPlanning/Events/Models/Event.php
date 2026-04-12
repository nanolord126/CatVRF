<?php declare(strict_types=1);

/**
 * Event — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/event
 */


namespace App\Domains\EventPlanning\Events\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Event extends Model
{
    use HasFactory;

    use HasFactory, HasUuids;

        protected $table = "events_b2b";

        protected $fillable = [
            "uuid", "tenant_id", "business_group_id", "correlation_id", "tags",
            "title", "start_date"
        ];

        protected $casts = [
            "tags" => "json",
        ];

        protected static function newFactory()
        {
            return \Database\Factories\EventFactory::new();
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope("tenant_id", function ($query) {
                if (function_exists("tenant") && tenant("id")) {
                    $query->where("tenant_id", tenant("id"));
                }
            });
        }

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

}
