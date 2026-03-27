<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\VehicleInspectionResource\Pages;

use App\Domains\Auto\Filament\Resources\VehicleInspectionResource;
use App\Domains\Auto\Events\VehicleInspectionPassed;
use App\Domains\Auto\Events\VehicleInspectionFailed;
use App\Services\FraudControlService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateVehicleInspection extends CreateRecord
{
    protected static string $resource = VehicleInspectionResource::class;

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
            Log::channel('audit')->info('VehicleInspection created', [
                'correlation_id' => $this->record->correlation_id,
                'inspection_id' => $this->record->id,
                'vehicle_id' => $this->record->vehicle_id,
                'status' => $this->record->status,
            ]);

            if ($this->record->status === 'passed') {
                event(new VehicleInspectionPassed(
                    $this->record,
                    $this->record->correlation_id
                ));
                $this->notification->make()
                    ->success()
                    ->title('Техосмотр пройден')
                    ->body('Сертификат выдан: ' . $this->record->certificate_number)
                    ->send();
            } elseif ($this->record->status === 'failed') {
                event(new VehicleInspectionFailed(
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
