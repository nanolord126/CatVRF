declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\ServiceWarranty;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final /**
 * ServiceWarrantyClaimSubmitted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ServiceWarrantyClaimSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ServiceWarranty $warranty,
        public readonly string $correlationId
    ) {
        $this->log->channel('audit')->info('ServiceWarrantyClaimSubmitted event dispatched', [
            'correlation_id' => $this->correlationId,
            'warranty_id' => $this->warranty->id,
            'warranty_number' => $this->warranty->warranty_number,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->warranty->tenant_id),
            new PrivateChannel('user.' . $this->warranty->client_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'warranty.service.claim.submitted';
    }
}
