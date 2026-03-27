<?php

declare(strict_types=1);


namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\VehicleInsurance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final /**
 * InsurancePolicyCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class InsurancePolicyCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly VehicleInsurance $insurance,
        public readonly string $correlationId
    ) {
        Log::channel('audit')->info('InsurancePolicyCreated event dispatched', [
            'correlation_id' => $this->correlationId,
            'insurance_id' => $this->insurance->id,
            'policy_number' => $this->insurance->policy_number,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->insurance->tenant_id),
            new PrivateChannel('user.' . $this->insurance->owner_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'insurance.policy.created';
    }
}
