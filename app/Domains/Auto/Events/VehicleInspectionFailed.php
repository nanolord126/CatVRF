<?php

declare(strict_types=1);


namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\VehicleInspection;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final /**
 * VehicleInspectionFailed
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class VehicleInspectionFailed implements ShouldBroadcast
{
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
