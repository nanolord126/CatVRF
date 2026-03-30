<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleInsuranceResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateVehicleInsurance extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = VehicleInsuranceResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $correlationId = Str::uuid()->toString();
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = Str::uuid()->toString();
            $data['correlation_id'] = $correlationId;

            return $data;
        }

        protected function afterCreate(): void
        {
            DB::transaction(function () {
                Log::channel('audit')->info('VehicleInsurance created', [
                    'correlation_id' => $this->record->correlation_id,
                    'policy_id' => $this->record->id,
                    'policy_number' => $this->record->policy_number,
                    'policy_type' => $this->record->policy_type,
                ]);

                event(new InsurancePolicyCreated(
                    $this->record,
                    $this->record->correlation_id
                ));
            });

            $this->notification->make()
                ->success()
                ->title('Полис оформлен')
                ->body('Номер полиса: ' . $this->record->policy_number)
                ->send();
        }
}
