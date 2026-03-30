<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\TowingRequestResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateTowingRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = TowingRequestResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $correlationId = Str::uuid()->toString();
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = Str::uuid()->toString();
            $data['correlation_id'] = $correlationId;

            $fraudCheck = app(FraudControlService::class)->check([
                'operation_type' => 'towing_request',
                'user_id' => auth()->id(),
                'amount' => $data['price'] ?? 0,
                'correlation_id' => $correlationId,
            ]);

            if ($fraudCheck['blocked']) {
                Log::channel('fraud_alert')->warning('Towing request blocked', [
                    'correlation_id' => $correlationId,
                ]);
                throw new \Exception('Операция заблокирована системой безопасности');
            }

            return $data;
        }

        protected function afterCreate(): void
        {
            DB::transaction(function () {
                Log::channel('audit')->info('TowingRequest created', [
                    'correlation_id' => $this->record->correlation_id,
                    'request_id' => $this->record->id,
                ]);

                event(new TowingRequestCreated(
                    $this->record,
                    $this->record->correlation_id
                ));
            });

            $this->notification->make()
                ->success()
                ->title('Заявка на эвакуатор создана')
                ->body('Ожидайте назначения водителя')
                ->send();
        }
}
