<?php declare(strict_types=1);

namespace App\Domains\Photography\Services;

use Illuminate\Support\Facades\DB;

use App\Domains\Photography\Models\PhotoSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class PhotographyService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function bookSession(array $data): PhotoSession
    {
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
        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
}