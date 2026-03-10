<?php

namespace App\Domains\Insurance\Services;

use App\Domains\Insurance\Models\InsurancePolicy;
use App\Models\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class InsuranceService
{
    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    public function createPolicy(array $data): InsurancePolicy
    {
        try {
            return DB::transaction(function () use ($data) {
                $policy = InsurancePolicy::create([...$data, 'tenant_id' => tenant()->id]);
                AuditLog::create([
                    'entity_type' => 'InsurancePolicy',
                    'entity_id' => $policy->id,
                    'action' => 'create',
                    'correlation_id' => $this->correlationId,
                    'user_id' => auth()->id(),
                ]);
                return $policy;
            });
        } catch (Throwable $e) {
            Log::error('InsuranceService.createPolicy failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function activatePolicy(InsurancePolicy $policy): InsurancePolicy
    {
        return DB::transaction(function () use ($policy) {
            $policy->update(['status' => 'active', 'activated_at' => now()]);
            return $policy;
        });
    }

    public function fileClaim(InsurancePolicy $policy, array $claim): bool
    {
        return DB::transaction(function () use ($policy, $claim) {
            return true;
        });
    }
}
