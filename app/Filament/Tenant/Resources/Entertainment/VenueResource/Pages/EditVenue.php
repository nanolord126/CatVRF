<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\VenueResource\Pages;

use App\Filament\Tenant\Resources\Entertainment\VenueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026 — EDIT VENUE PAGE (Entertainment Domain)
 * 1. final class
 * 2. Audit logging with correlation_id
 * 3. Fraud check (placeholder call)
 */
final class EditVenue extends EditRecord
{
    protected static string $resource = VenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        Log::channel('audit')->info('Venue modification started', [
            'venue_id' => $this->record->id,
            'user_id' => auth()->id(),
            'correlation_id' => $this->record->correlation_id,
        ]);
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('Venue modification completed', [
            'venue_id' => $this->record->id,
            'user_id' => auth()->id(),
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
