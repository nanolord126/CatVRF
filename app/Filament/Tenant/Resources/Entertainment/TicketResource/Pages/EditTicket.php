<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\TicketResource\Pages;

use App\Filament\Tenant\Resources\Entertainment\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026 — EDIT TICKET PAGE (Entertainment Domain)
 */
final class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        Log::channel('audit')->info('Entertainment Ticket modification', [
            'ticket_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
