<?php

namespace App\Filament\Tenant\Resources\Finance\SettlementDocumentResource\Pages;

use App\Filament\Tenant\Resources\Finance\SettlementDocumentResource;
use Filament\Resources\Pages\ListRecords;

class ListSettlementDocuments extends ListRecords
{
    protected static string $resource = SettlementDocumentResource::class;
}
