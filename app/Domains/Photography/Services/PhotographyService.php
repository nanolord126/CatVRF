<?php declare(strict_types=1);

namespace App\Domains\Photography\Services;

use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;
use Illuminate\Support\Str;


use Illuminate\Support\Facades\DB;

use App\Domains\Photography\Models\PhotoSession;

final class PhotographyService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Photography', ['correlation_id' => $correlationId]);
        FraudControlService::check('service_operation', ['correlation_id' => $correlationId]);

        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function bookSession(array $data): PhotoSession
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Photography', ['correlation_id' => $correlationId]);
        FraudControlService::check('service_operation', ['correlation_id' => $correlationId]);

        $session = PhotoSession::create([
            'tenant_id' => auth()->user()->tenant_id,
            'uuid' => Str::uuid(),
            'correlation_id' => $this->correlationId,
            'client_id' => auth()->id(),
            'photographer_id' => $data['photographer_id'],
            'datetime' => $data['datetime'],
            'location' => $data['location'],
            'price' => $data['price'],
            'status' => 'pending',
        ]);

        Log::channel('audit')->info('Photo session booked', [
            'correlation_id' => $this->correlationId,
            'session_id' => $session->id,
        ]);

        return $session;
    }

    /**
     * Выполняет операцию в транзакции с аудитом.
     */
    public function executeInTransaction(callable $callback)
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Photography', ['correlation_id' => $correlationId]);
        FraudControlService::check('service_operation', ['correlation_id' => $correlationId]);

        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
}