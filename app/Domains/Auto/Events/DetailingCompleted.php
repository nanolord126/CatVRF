declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\CarDetailing;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final /**
 * DetailingCompleted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DetailingCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CarDetailing $detailing,
        public readonly string $correlationId
    ) {
        $this->log->channel('audit')->info('DetailingCompleted event dispatched', [
            'correlation_id' => $this->correlationId,
            'detailing_id' => $this->detailing->id,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->detailing->tenant_id),
            new PrivateChannel('user.' . $this->detailing->client_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'detailing.completed';
    }
}
