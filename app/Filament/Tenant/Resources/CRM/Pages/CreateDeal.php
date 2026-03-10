<?php

namespace App\Filament\Tenant\Resources\CRM\Pages;

use App\Filament\Tenant\Resources\CRM\DealResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDeal extends CreateRecord
{
    protected static string $resource = DealResource::class;
}
