<?php

declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleInspectionResource\Pages;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\DatabaseManager;

final class CreateVehicleInspection extends CreateRecord
{
    protected static string $resource = VehicleInspectionResource::class;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger) {}

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
        $this->db->transaction(function () {
            $this->logger->$this->logger->info('VehicleInspection created', [
                'correlation_id' => $this->record->correlation_id,
                'inspection_id' => $this->record->id,
                'vehicle_id' => $this->record->vehicle_id,
                'status' => $this->record->status,
            ]);

            if ($this->record->status === 'passed') {
                $this->eventDispatcher->dispatch(new VehicleInspectionPassed(
                    $this->record,
                    $this->record->correlation_id
                ));
                $this->notification->make()
                    ->success()
                    ->title('Техосмотр пройден')
                    ->body('Сертификат выдан: '.$this->record->certificate_number)
                    ->send();
            } elseif ($this->record->status === 'failed') {
                $this->eventDispatcher->dispatch(new VehicleInspectionFailed(
                    $this->record,
                    $this->record->correlation_id
                ));
                $this->notification->make()
                    ->warning()
                    ->title('Техосмотр не пройден')
                    ->body('Требуется устранение замечаний')
                    ->send();
            }
        });
    }
}
