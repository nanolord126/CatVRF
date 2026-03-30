<?php declare(strict_types=1);

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InsurancePolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'insurance_policies';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'company_id',
            'type_id',
            'user_id',
            'policy_number',
            'premium_amount',
            'coverage_amount',
            'starts_at',
            'expires_at',
            'status',
            'policy_data',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'policy_data' => 'json',
            'tags' => 'json',
            'premium_amount' => 'integer',
            'coverage_amount' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'status' => 'string',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->policy_number)) {
                    $model->policy_number = 'POL-' . strtoupper(Str::random(10));
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                if (auth()->check()) {
                    $builder->where('tenant_id', auth()->user()->tenant_id);
                }
            });
        }

        /**
         * The insurance company that issued this policy.
         */
        public function company(): BelongsTo
        {
            return $this->belongsTo(InsuranceCompany::class, 'company_id');
        }

        /**
         * The specific type of insurance (e.g., KASKO, OSAGO).
         */
        public function type(): BelongsTo
        {
            return $this->belongsTo(InsuranceType::class, 'type_id');
        }

        /**
         * The policy holder.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class, 'user_id');
        }

        /**
         * Claims filed under this policy.
         */
        public function claims(): HasMany
        {
            return $this->hasMany(InsuranceClaim::class, 'policy_id');
        }

        /**
         * The signed legal contract for this policy.
         */
        public function contract(): HasOne
        {
            return $this->hasOne(InsuranceContract::class, 'policy_id');
        }

        /**
         * Check if the policy is currently active based on date and status.
         */
        public function isActive(): bool
        {
            return $this->status === 'active' &&
                now()->between($this->starts_at, $this->expires_at);
        }
}
