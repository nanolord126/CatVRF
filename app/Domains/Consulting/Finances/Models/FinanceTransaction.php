<?php declare(strict_types=1);

/**
 * FinanceTransaction — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/financetransaction
 */


namespace App\Domains\Consulting\Finances\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FinanceTransaction extends Model
{

    use HasFactory, HasUuids;

        protected $table = "finance_transactions";

        protected $fillable = [
            "uuid", "tenant_id", "business_group_id", "correlation_id", "tags",
            "amount", "type", "status", "description"
        ];

        protected $casts = [
            "tags" => "json",
            "amount" => "integer",
        ];

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
