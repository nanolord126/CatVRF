<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use Illuminate\Support\Facades\DB;

use App\Domains\HomeServices\Models\HomeServiceJob;
use Illuminate\Support\Str;

final class HomeServicesService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function bookService(array $data, int $userId, int $tenantId): HomeServiceJob
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($data, $userId, $tenantId) {
        $job = HomeServiceJob::create([
            'tenant_id' => $tenantId,
            'uuid' => Str::uuid(),
            'correlation_id' => $this->correlationId,
            'contractor_id' => $data['contractor_id'],
            'client_id' => $userId,
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
        });
    }

    /**
     * Выполняет операцию в транзакции с аудитом.
     */
    public function executeInTransaction(callable $callback)
    {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
}