<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\TicketResource\Pages;

use App\Filament\Tenant\Resources\Entertainment\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026 — LIST TICKETS PAGE (Entertainment Domain)
 */
final class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->after(function () {
                    Log::channel('audit')->info('Entertainment Ticket creation via panel started', [
                        'tenant_id' => filament()->getTenant()->id,
                        'user_id' => auth()->id(),
                    ]);
                }),
        ];
    }
}
