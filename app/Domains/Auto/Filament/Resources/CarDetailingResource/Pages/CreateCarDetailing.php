<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\CarDetailingResource\Pages;

use App\Domains\Auto\Filament\Resources\CarDetailingResource;
use App\Domains\Auto\Events\CarDetailingBookingCreated;
use App\Services\FraudControlService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CreateCarDetailing extends CreateRecord
{
    protected static string $resource = CarDetailingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = Str::uuid()->toString();
        $data['correlation_id'] = $correlationId;

        // Fraud control check
        $fraudCheck = app(FraudControlService::class)->check([
            'operation_type' => 'detailing_booking',
            'user_id' => auth()->id(),
            'amount' => $data['price'] ?? 0,
            'correlation_id' => $correlationId,
        ]);

        if ($fraudCheck['blocked']) {
            Log::channel('fraud_alert')->warning('Detailing booking blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id' => auth()->id(),
            ]);
            throw new \Exception('Операция заблокирована системой безопасности');
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        DB::transaction(function () {
            Log::channel('audit')->info('CarDetailing created', [
                'correlation_id' => $this->record->correlation_id,
                'detailing_id' => $this->record->id,
                'vehicle_id' => $this->record->vehicle_id,
                'user_id' => auth()->id(),
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
