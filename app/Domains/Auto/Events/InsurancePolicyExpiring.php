<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InsurancePolicyExpiring extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
