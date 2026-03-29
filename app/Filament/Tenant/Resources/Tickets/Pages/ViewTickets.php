<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Tickets\Pages;
use App\Filament\Tenant\Resources\TicketsResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordTickets extends ViewRecord {
    protected static string $resource = TicketsResource::class;
}
