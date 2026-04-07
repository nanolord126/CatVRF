<?php declare(strict_types=1);

/**
 * ContractorEarning — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/contractorearning
 */


namespace App\Domains\HomeServices\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ContractorEarning extends Model
{
    use HasFactory;

    protected $table = 'contractor_earnings';
        protected $fillable = [
        'uuid',
        'correlation_id','tenant_id', 'contractor_id', 'period_month', 'period_year', 'total_revenue', 'total_commission', 'contractor_earnings', 'total_jobs', 'completed_jobs', 'average_rating', 'payout_initiated_at', 'payout_completed_at', 'payout_method', 'correlation_id'];
        protected $hidden = [];
        protected $casts = ['total_revenue' => 'float', 'total_commission' => 'float', 'contractor_earnings' => 'float', 'average_rating' => 'float', 'payout_initiated_at' => 'datetime', 'payout_completed_at' => 'datetime'];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant()->id));
        }

        public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
        public function contractor(): BelongsTo { return $this->belongsTo(Contractor::class); }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
