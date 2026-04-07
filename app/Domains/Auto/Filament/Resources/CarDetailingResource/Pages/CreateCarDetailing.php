<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\CarDetailingResource\Pages;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;

final class CreateCarDetailing extends CreateRecord
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    protected static string $resource = CarDetailingResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $correlationId = Str::uuid()->toString();
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = Str::uuid()->toString();
            $data['correlation_id'] = $correlationId;

            // Fraud control check
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'detailing_booking', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudCheck['blocked']) {
                $this->logger->warning('Detailing booking blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id' => $this->guard->id(),
                ]);
                throw new \RuntimeException('Операция заблокирована системой безопасности');
            }

            return $data;
        }

        protected function afterCreate(): void
        {
            $this->db->transaction(function () {
                $this->logger->info('CarDetailing created', [
                    'correlation_id' => $this->record->correlation_id,
                    'detailing_id' => $this->record->id,
                    'vehicle_id' => $this->record->vehicle_id,
                    'user_id' => $this->guard->id(),
                ]);

                event(new CarDetailingBookingCreated(
                    $this->record,
                    $this->record->correlation_id
                ));
            });

            $this->notification->make()
                ->success()
                ->title('Детейлинг запланирован')
                ->body('Бронирование создано успешно')
                ->send();
        }
}
