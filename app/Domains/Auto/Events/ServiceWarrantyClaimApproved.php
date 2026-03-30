<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceWarrantyClaimApproved extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly ServiceWarranty $warranty,
            public readonly string $correlationId
        ) {
            Log::channel('audit')->info('ServiceWarrantyClaimApproved event dispatched', [
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
            return 'warranty.service.claim.approved';
        }
}
