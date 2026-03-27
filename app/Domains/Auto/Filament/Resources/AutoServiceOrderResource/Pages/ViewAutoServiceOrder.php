<?php

declare(strict_types=1);


namespace App\Domains\Auto\Filament\Resources\AutoServiceOrderResource\Pages;

use App\Domains\Auto\Filament\Resources\AutoServiceOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

/**
 * Просмотр детальной информации о заказе-наряде СТО с audit-логом.
 * Production 2026.
 */
final class ViewAutoServiceOrder extends ViewRecord
{
    protected static string $resource = AutoServiceOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\DeleteAction::make()
                ->after(function () {
                    Log::channel('audit')->info('Auto service order deleted from view page', [
                        'correlation_id' => $this->record->correlation_id,
                        'order_id' => $this->record->id,
                        'user_id' => auth()->id(),
                    ]);
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        Log::channel('audit')->info('Auto service order viewed', [
            'correlation_id' => $this->record->correlation_id,
            'order_id' => $this->record->id,
            'service_type' => $this->record->service_type,
            'status' => $this->record->status,
            'user_id' => auth()->id(),
        ]);

        return $data;
    }
}
