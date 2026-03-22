<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\VehicleInsurance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class InsurancePolicyExpiring implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly VehicleInsurance $insurance,
        public readonly int $daysUntilExpiry,
        public readonly string $correlationId
    ) {
        Log::channel('audit')->info('InsurancePolicyExpiring event dispatched', [
            'correlation_id' => $this->correlationId,
            'insurance_id' => $this->insurance->id,
            'days_until_expiry' => $this->daysUntilExpiry,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->insurance->owner_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'insurance.policy.expiring';
    }
}
