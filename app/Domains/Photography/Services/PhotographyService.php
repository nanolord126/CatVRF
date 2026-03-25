<?php declare(strict_types=1);

namespace App\Domains\Photography\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\FraudControlService;


use Illuminate\Support\Facades\DB;

use App\Domains\Photography\Models\PhotoSession;

final class PhotographyService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function bookSession(array $data, int $clientId, int $tenantId): PhotoSession
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
$this->db->transaction(function () use ($data, $clientId, $tenantId) {
        $session = Photo$this->session->create([
            'tenant_id' => $tenantId,
            'uuid' => Str::uuid(),
            'correlation_id' => $this->correlationId,
            'client_id' => $clientId,
            'photographer_id' => $data['photographer_id'],
            'datetime' => $data['datetime'],
            'location' => $data['location'],
            'price' => $data['price'],
            'status' => 'pending',
        ]);

        $this->log->channel('audit')->info('Photo session booked', [
            'correlation_id' => $this->correlationId,
            'session_id' => $session->id,
        ]);

        return $session;
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
$this->db->transaction(function () use ($callback) {
            return $callback();
        });
    }
}