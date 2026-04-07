<?php declare(strict_types=1);

namespace App\Models\CarRental;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class Insurance extends Model
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    use HasFactory;

    protected $table = 'car_insurances';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'daily_cost',
            'deductible',
            'description',
            'correlation_id',
        ];

        /**
         * Casting logic for nested JSON structures.
         */
        protected $casts = [
            'daily_cost' => 'integer',
            'deductible' => 'integer',
            'uuid' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        /**
         * Boot logic for tenant-aware scoping.
         */
        protected static function booted(): void
        {
            // 1. Force Tenant Scoping via global scope
            static::addGlobalScope('tenant', function (Builder $builder) {
                $tenantId = tenant()->id ?? $this->config->get('multitenancy.default_tenant_id');
                if ($tenantId) {
                    $builder->where('car_insurances.tenant_id', $tenantId);
                }
            });

            // 2. Automatic UUID generation and correlation assignment
            static::creating(function (self $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
                if (empty($model->tenant_id)) {
                    $model->tenant_id = tenant()->id ?? 1;
                }
            });
        }

        /**
         * Relationship: Bookings utilizing this insurance plan.
         */
        public function bookings(): \Illuminate\Database\Eloquent\Relations\HasMany
        {
            return $this->hasMany(Booking::class, 'insurance_id');
        }

        /**
         * Human-readable formatting for daily cost.
         */
        public function getFormattedCostAttribute(): string
        {
            return number_format($this->daily_cost / 100, 2, '.', ' ') . ' ₽ / day';
        }

        /**
         * Franchise (Deductible) formatting.
         */
        public function getFormattedDeductibleAttribute(): string
        {
            return number_format($this->deductible / 100, 2, '.', ' ') . ' ₽';
        }

        /**
         * Helper to retrieve summary info for cards.
         */
        public function getSummaryLabel(): string
        {
            $franchise = $this->deductible > 0 ? "Franchise: {$this->getFormattedDeductibleAttribute()}" : "Zero Franchise (Full Protection)";
            return "{$this->name} - {$franchise}";
        }

        /**
         * Correlation Tracking implementation.
         */
        public function getActiveTraceId(): string
        {
            return (string) ($this->correlation_id ?? 'root-trace-id');
        }
}
