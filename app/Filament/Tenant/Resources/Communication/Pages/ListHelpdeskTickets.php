<?php

namespace App\Filament\Tenant\Resources\Communication\Pages;

use App\Filament\Tenant\Resources\Communication\HelpdeskTicketResource;
use Filament\Resources\Pages\ListRecords;

class ListHelpdeskTickets extends ListRecords
{
    protected static string $resource = HelpdeskTicketResource::class;
}
