<?php

declare(strict_types=1);


namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\TowingRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final /**
 * TowingCompleted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TowingCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TowingRequest $request,
        public readonly string $correlationId
    ) {
        Log::channel('audit')->info('TowingCompleted event dispatched', [
            'correlation_id' => $this->correlationId,
            'request_id' => $this->request->id,
            'dropoff_location' => $this->request->dropoff_location,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->request->tenant_id),
            new PrivateChannel('user.' . $this->request->client_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'towing.completed';
    }
}
