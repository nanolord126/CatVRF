<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\B2BDeal\Pages;

use use App\Filament\Tenant\Resources\B2BDealResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewB2BDeal extends ViewRecord
{
    protected static string $resource = B2BDealResource::class;

    public function getTitle(): string
    {
        return 'View B2BDeal';
    }
}