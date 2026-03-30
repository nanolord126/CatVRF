<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\CarWashBookingResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateCarWashBooking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = CarWashBookingResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $correlationId = Str::uuid()->toString();
            $data['correlation_id'] = $correlationId;
            $data['status'] = 'pending';

            // Fraud check перед созданием
            $fraudCheck = FraudControlService::check([
                'operation' => 'create_car_wash_booking',
                'tenant_id' => filament()->getTenant()->id,
                'user_id' => auth()->id(),
                'data' => $data,
            ]);

            if (!$fraudCheck['allowed']) {
                $this->notification->make()
                    ->title('Подозрение на мошенничество')
                    ->body($fraudCheck['reason'] ?? 'Операция заблокирована')
                    ->danger()
                    ->send();

                $this->halt();
            }

            Log::channel('audit')->info('Creating car wash booking', [
                'correlation_id' => $correlationId,
                'tenant_id' => filament()->getTenant()->id,
                'user_id' => auth()->id(),
                'data' => $data,
            ]);

            return $data;
        }

        protected function afterCreate(): void
        {
            DB::transaction(function () {
                // Событие создания брони мойки
                event(new CarWashBookingCreated(
                    $this->record,
                    $this->record->correlation_id
                ));

                Log::channel('audit')->info('Car wash booking created successfully', [
                    'correlation_id' => $this->record->correlation_id,
                    'booking_id' => $this->record->id,
                    'wash_type' => $this->record->wash_type,
                    'scheduled_at' => $this->record->scheduled_at,
                    'tenant_id' => filament()->getTenant()->id,
                ]);

                $this->notification->make()
                    ->title('Бронь мойки создана')
                    ->body("Тип мойки: {$this->record->wash_type}, дата: {$this->record->scheduled_at->format('d.m.Y H:i')}")
                    ->success()
                    ->send();
            });
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
