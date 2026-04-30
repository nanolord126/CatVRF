<?php

declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\CarDetailingResource\Pages;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\DatabaseManager;

final class CreateCarDetailing extends CreateRecord
{
    protected static string $resource = CarDetailingResource::class;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard) {}

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
            $this->logger->$this->logger->info('CarDetailing created', [
                'correlation_id' => $this->record->correlation_id,
                'detailing_id' => $this->record->id,
                'vehicle_id' => $this->record->vehicle_id,
                'user_id' => $this->guard->id(),
            ]);

            $this->eventDispatcher->dispatch(new CarDetailingBookingCreated(
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
