<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Tickets\Pages;
use App\Filament\Tenant\Resources\TicketsResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordTickets extends CreateRecord {
    protected static string $resource = TicketsResource::class;
}
