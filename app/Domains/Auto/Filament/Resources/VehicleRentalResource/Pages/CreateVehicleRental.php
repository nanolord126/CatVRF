<?php

declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleRentalResource\Pages;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\DatabaseManager;

final class CreateVehicleRental extends CreateRecord
{
    protected static string $resource = VehicleRentalResource::class;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard) {}

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = Str::uuid()->toString();
        $data['correlation_id'] = $correlationId;

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'vehicle_rental', amount: 0, correlationId: $correlationId ?? '');

        if ($fraudCheck['blocked']) {
            throw new \RuntimeException('Операция заблокирована системой безопасности');
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->db->transaction(function () {
            $this->logger->$this->logger->info('VehicleRental created', [
                'correlation_id' => $this->record->correlation_id,
                'rental_id' => $this->record->id,
            ]);

            if ($this->record->status === 'active') {
                $this->eventDispatcher->dispatch(new VehicleRentalStarted(
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
