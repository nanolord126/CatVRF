<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Tickets\Pages;

use use App\Filament\Tenant\Resources\TicketsResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewTickets extends ViewRecord
{
    protected static string $resource = TicketsResource::class;

    public function getTitle(): string
    {
        return 'View Tickets';
    }
}