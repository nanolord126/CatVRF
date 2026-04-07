<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleRentalResource\Pages;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;

final class CreateVehicleRental extends CreateRecord
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    protected static string $resource = VehicleRentalResource::class;

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
                $this->logger->info('VehicleRental created', [
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

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
