<?php declare(strict_types=1);

namespace App\Domains\Freelance\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

use App\Domains\Freelance\Models\FreelanceJob;
use Illuminate\Support\Str;

final class FreelanceService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function postJob(array $data): FreelanceJob
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'postJob'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL postJob', ['domain' => __CLASS__]);

        $job = FreelanceJob::create([
            'tenant_id' => auth()->user()->tenant_id,
            'uuid' => Str::uuid(),
            'correlation_id' => $this->correlationId,
            'title' => $data['title'],
            'description' => $data['description'],
            'budget' => $data['budget'],
            'deadline' => $data['deadline'],
            'status' => 'open',
        ]);

        Log::channel('audit')->info('Freelance job posted', [
            'correlation_id' => $this->correlationId,
            'job_id' => $job->id,
        ]);

        return $job;
    }

    /**
     * Выполняет операцию в транзакции с аудитом.
     */
    public function executeInTransaction(callable $callback)
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'executeInTransaction'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL executeInTransaction', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
}