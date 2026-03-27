<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\TicketResource\Pages;

use App\Filament\Tenant\Resources\Entertainment\TicketResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — CREATE TICKET PAGE (Entertainment Domain)
 */
final class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = (string) Str::uuid();
        $data['correlation_id'] = (string) Str::uuid();

        Log::channel('audit')->info('Entertainment Ticket record mutation', [
            'tenant_id' => $data['tenant_id'],
            'correlation_id' => $data['correlation_id'],
        ]);

        return $data;
    }
}
