<?php

declare(strict_types=1);

namespace App\Models\Insurance;

use Carbon\CarbonImmutable;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class InsuranceContract extends Model
{
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

    public function __construct(
        private readonly Request $request,
    ) {}

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
            'signed_at' => CarbonImmutable::now(),
            'digital_signature' => array_merge($signature, [
                'signed_from_ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ]),
        ]);

        return true;
    }

    /**
     * Verification: Is contract legally binding and finalized?
     */
    public function isFinalized(): bool
    {
        return $this->signed_at !== null && ! empty($this->digital_signature);
    }

    protected static function booted(): void
    {
        self::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        self::addGlobalScope('tenant', function ($builder) {
            if ($this->guard->check()) {
                $builder->where('tenant_id', $this->guard->user()->tenant_id);
            }
        });
    }
}
