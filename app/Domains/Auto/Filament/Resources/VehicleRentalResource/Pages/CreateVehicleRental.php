<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleRentalResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateVehicleRental extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
            DB::transaction(function () {
                Log::channel('audit')->info('VehicleRental created', [
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
