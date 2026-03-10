<?php

namespace App\Filament\Tenant\Resources\PayoutResource\Pages;

use App\Filament\Tenant\Resources\PayoutResource;
use Filament\Resources\Pages\ListRecords;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;
}
