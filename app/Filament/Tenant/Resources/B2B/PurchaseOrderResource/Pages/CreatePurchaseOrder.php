<?php

namespace App\Filament\Tenant\Resources\B2B\PurchaseOrderResource\Pages;

use App\Filament\Tenant\Resources\B2B\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;
}
