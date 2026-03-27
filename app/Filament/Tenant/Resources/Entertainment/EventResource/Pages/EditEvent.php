<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\EventResource\Pages;

use App\Filament\Tenant\Resources\Entertainment\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026 — EDIT EVENT PAGE (Entertainment Domain)
 * 1. final class
 * 2. Audit logging with correlation_id
 */
final class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        Log::channel('audit')->info('Entertainment Event modification started', [
            'event_id' => $this->record->id,
            'user_id' => auth()->id(),
            'correlation_id' => $this->record->correlation_id,
        ]);
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('Entertainment Event modification completed', [
            'event_id' => $this->record->id,
            'user_id' => auth()->id(),
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
