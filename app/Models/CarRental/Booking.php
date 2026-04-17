<?php declare(strict_types=1);

namespace App\Models\CarRental;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class Booking extends Model
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}


        protected $table = 'car_bookings';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'car_id',
            'insurance_id',
            'starts_at',
            'ends_at',
            'daily_price',
            'total_price',
            'deposit_amount',
            'status',
            'is_b2b',
            'firm_name',
            'check_in_data',
            'check_out_data',
            'idempotency_key',
            'correlation_id',
        ];

        /**
         * Property casting logic for features and photos.
         */
        protected $casts = [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'check_in_data' => 'json',
            'check_out_data' => 'json',
            'daily_price' => 'integer',
            'total_price' => 'integer',
            'deposit_amount' => 'integer',
            'is_b2b' => 'boolean',
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
                    $builder->where('car_bookings.tenant_id', $tenantId);
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
         * Relationship: The vehicle assigned for this reservation.
         */
        public function car(): BelongsTo
        {
            return $this->belongsTo(Car::class, 'car_id');
        }

        /**
         * Relationship: Associated Customer/Account.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }

        /**
         * Relationship: Selected insurance package.
         */
        public function insurance(): BelongsTo
        {
            return $this->belongsTo(Insurance::class, 'insurance_id');
        }

        /**
         * Calculation of duration in full days for pricing.
         */
        public function getDurationDaysAttribute(): int
        {
            if (!$this->starts_at || !$this->ends_at) {
                return 0;
            }

            $diff = $this->starts_at->diffInDays($this->ends_at);
            return max((int)$diff, 1);
        }

        /**
         * Status formatting for UI badges/colors.
         */
        public function getStatusLabel(): string
        {
            return match ($this->status) {
                'confirmed' => 'Active Contract',
                'picked_up' => 'Vehicle with Client',
                'returned' => 'Closed (Success)',
                'cancelled' => 'Cancelled',
                'disputed' => 'Under Dispute!',
                default => 'Unknown Status',
            };
        }

        /**
         * Correlation Tracking implementation.
         */
        public function getActiveTraceId(): string
        {
            return (string) ($this->correlation_id ?? 'root-trace-id');
        }

        /**
         * Business check for deposit status.
         */
        public function isDepositHeld(): bool
        {
            return in_array($this->status, ['confirmed', 'picked_up', 'returned']);
        }
}
