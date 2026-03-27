<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VIPBookingResource\Pages;

use App\Filament\Tenant\Resources\VIPBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * EditVIPBooking
 * 
 * Layer 1-3: Filament Pages
 * Редактирование бронирования с аудитом.
 * 
 * @version 1.0.0
 * @author CatVRF
 */
final class EditVIPBooking extends EditRecord
{
    protected static string $resource = VIPBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();

        Log::channel('audit')->info('Editing VIP Booking via Filament', [
            'booking_id' => $this->record->id,
            'user_id' => auth()->id(),
            'correlation_id' => $data['correlation_id'],
        ]);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
