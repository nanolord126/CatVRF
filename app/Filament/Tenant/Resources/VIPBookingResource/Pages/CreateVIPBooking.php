<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VIPBookingResource\Pages;

use App\Filament\Tenant\Resources\VIPBookingResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CreateVIPBooking
 * 
 * Layer 1-3: Filament Pages
 * Создание бронирования с аудитом.
 * 
 * @version 1.0.0
 * @author CatVRF
 */
final class CreateVIPBooking extends CreateRecord
{
    protected static string $resource = VIPBookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();

        Log::channel('audit')->info('Creating VIP Booking via Filament', [
            'client_id' => $data['client_id'] ?? 'N/A',
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
