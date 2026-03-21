<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Services;

use Illuminate\Support\Facades\DB;

use App\Domains\HomeServices\Models\HomeServiceJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class HomeServicesService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function bookService(array $data): HomeServiceJob
    {
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
        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
}