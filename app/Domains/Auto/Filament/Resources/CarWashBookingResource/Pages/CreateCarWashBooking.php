<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\CarWashBookingResource\Pages;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;

final class CreateCarWashBooking extends CreateRecord
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    protected static string $resource = CarWashBookingResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $correlationId = Str::uuid()->toString();
            $data['correlation_id'] = $correlationId;
            $data['status'] = 'pending';

            // Fraud check перед созданием
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'create_car_wash_booking', amount: 0, correlationId: $correlationId ?? '');

            if (!$fraudCheck['allowed']) {
                $this->notification->make()
                    ->title('Подозрение на мошенничество')
                    ->body($fraudCheck['reason'] ?? 'Операция заблокирована')
                    ->danger()
                    ->send();

                $this->halt();
            }

            $this->logger->info('Creating car wash booking', [
                'correlation_id' => $correlationId,
                'tenant_id' => filament()->getTenant()->id,
                'user_id' => $this->guard->id(),
                'data' => $data,
            ]);

            return $data;
        }

        protected function afterCreate(): void
        {
            $this->db->transaction(function () {
                // Событие создания брони мойки
                event(new CarWashBookingCreated(
                    $this->record,
                    $this->record->correlation_id
                ));

                $this->logger->info('Car wash booking created successfully', [
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
