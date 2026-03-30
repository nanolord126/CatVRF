<?php declare(strict_types=1);

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InsuranceContract extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'insurance_contracts';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'policy_id',
            'document_url',
            'signed_at',
            'digital_signature',
            'correlation_id',
        ];

        protected $casts = [
            'digital_signature' => 'json',
            'signed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        /**
         * Boot logic for Insurance Contract: Automatic UUID and Tenant Scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                // Guarantee unique identification
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }

                // Map tenant context for strict data isolation
                if (empty($model->tenant_id) && auth()->check()) {
                    $model->tenant_id = auth()->user()->tenant_id;
                }
            });

            // Always apply tenant scope globally
            static::addGlobalScope('tenant', function ($builder) {
                if (auth()->check()) {
                    $builder->where('tenant_id', auth()->user()->tenant_id);
                }
            });
        }

        /**
         * Relationship: The policy related to this legal contract.
         */
        public function policy(): BelongsTo
        {
            return $this->belongsTo(InsurancePolicy::class, 'policy_id');
        }

        /**
         * Action: Mark the contract as signed with a cryptographically tracked footprint.
         * Implementation: Layer 2 Logic.
         */
        public function sign(array $signature): bool
        {
            if ($this->signed_at !== null) {
                return false; // Prevent double-signing
            }

            $this->update([
                'signed_at' => now(),
                'digital_signature' => array_merge($signature, [
                    'signed_from_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'timestamp' => now()->toIso8601String(),
                ]),
            ]);

            return true;
        }

        /**
         * Verification: Is contract legally binding and finalized?
         */
        public function isFinalized(): bool
        {
            return $this->signed_at !== null && !empty($this->digital_signature);
        }
}
