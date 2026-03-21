<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

use App\Domains\HomeServices\Models\HomeServiceJob;
use Illuminate\Support\Str;

final class HomeServicesService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function bookService(array $data): HomeServiceJob
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'bookService'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL bookService', ['domain' => __CLASS__]);

        $job = HomeServiceJob::create([
            'tenant_id' => auth()->user()->tenant_id,
            'uuid' => Str::uuid(),
            'correlation_id' => $this->correlationId,
            'contractor_id' => $data['contractor_id'],
            'client_id' => auth()->id(),
            'service_type' => $data['service_type'],
            'datetime' => $data['datetime'],
            'address' => $data['address'],
            'price' => $data['price'],
            'status' => 'pending',
        ]);

        Log::channel('audit')->info('Home service job booked', [
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