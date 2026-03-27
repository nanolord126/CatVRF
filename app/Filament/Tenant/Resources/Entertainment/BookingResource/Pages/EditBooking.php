<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\BookingResource\Pages;

use App\Filament\Tenant\Resources\Entertainment\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026 — EDIT BOOKING PAGE (Entertainment Domain)
 * 1. final class
 * 2. Audit logging with correlation_id
 */
final class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        Log::channel('audit')->info('Entertainment Booking modification started', [
            'booking_id' => $this->record->id,
            'user_id' => auth()->id(),
            'correlation_id' => $this->record->correlation_id,
        ]);
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('Entertainment Booking modification completed', [
            'booking_id' => $this->record->id,
            'user_id' => auth()->id(),
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
