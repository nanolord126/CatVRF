<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\BookingResource\Pages;

use App\Filament\Tenant\Resources\Entertainment\BookingResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — CREATE BOOKING PAGE (Entertainment Domain)
 * 1. final class
 * 2. Correlation ID injection
 * 3. Audit logging
 */
final class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = (string) Str::uuid();
        $data['correlation_id'] = (string) Str::uuid();
        
        Log::channel('audit')->info('Entertainment Booking record mutation before creation', [
            'tenant_id' => $data['tenant_id'],
            'correlation_id' => $data['correlation_id'],
            'user_id' => auth()->id(),
        ]);

        return $data;
    }

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('Entertainment Booking record created successfully', [
            'booking_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
        ]);
    }
}
