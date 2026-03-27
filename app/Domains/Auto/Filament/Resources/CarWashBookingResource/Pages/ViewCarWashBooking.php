<?php

declare(strict_types=1);


namespace App\Domains\Auto\Filament\Resources\CarWashBookingResource\Pages;

use App\Domains\Auto\Filament\Resources\CarWashBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

/**
 * Просмотр детальной информации о брони мойки с audit-логом.
 * Production 2026.
 */
final class ViewCarWashBooking extends ViewRecord
{
    protected static string $resource = CarWashBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\DeleteAction::make()
                ->after(function () {
                    Log::channel('audit')->info('Car wash booking deleted from view page', [
                        'correlation_id' => $this->record->correlation_id,
                        'booking_id' => $this->record->id,
                        'user_id' => auth()->id(),
                    ]);
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        Log::channel('audit')->info('Car wash booking viewed', [
            'correlation_id' => $this->record->correlation_id,
            'booking_id' => $this->record->id,
            'wash_type' => $this->record->wash_type,
            'status' => $this->record->status,
            'user_id' => auth()->id(),
        ]);

        return $data;
    }
}
