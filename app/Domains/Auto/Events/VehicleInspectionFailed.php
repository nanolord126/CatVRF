<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VehicleInspectionFailed extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly VehicleInspection $inspection,
            public readonly string $correlationId
        ) {
            Log::channel('audit')->info('VehicleInspectionFailed event dispatched', [
                'correlation_id' => $this->correlationId,
                'inspection_id' => $this->inspection->id,
                'notes' => $this->inspection->notes,
            ]);
        }

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('tenant.' . $this->inspection->tenant_id),
                new PrivateChannel('user.' . $this->inspection->client_id),
            ];
        }

        public function broadcastAs(): string
        {
            return 'inspection.failed';
        }
}
