<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleRentalResource\Pages;

use App\Domains\Auto\Filament\Resources\VehicleRentalResource;
use App\Domains\Auto\Events\VehicleRentalStarted;
use App\Services\FraudControlService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateVehicleRental extends CreateRecord
{
    protected static string $resource = VehicleRentalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = Str::uuid()->toString();
        $data['correlation_id'] = $correlationId;

        $fraudCheck = app(FraudControlService::class)->check([
            'operation_type' => 'vehicle_rental',
            'user_id' => auth()->id(),
            'amount' => $data['total_price'] ?? 0,
            'correlation_id' => $correlationId,
        ]);

        if ($fraudCheck['blocked']) {
            throw new \Exception('Операция заблокирована системой безопасности');
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->db->transaction(function () {
            $this->log->channel('audit')->info('VehicleRental created', [
                'correlation_id' => $this->record->correlation_id,
                'rental_id' => $this->record->id,
            ]);

            if ($this->record->status === 'active') {
                event(new VehicleRentalStarted(
                    $this->record,
                    $this->record->correlation_id
                ));
            }
        });

        $this->notification->make()
            ->success()
            ->title('Аренда оформлена')
            ->send();
    }
}
