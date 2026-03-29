<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\B2BDeal\Pages;

use use App\Filament\Tenant\Resources\B2BDealResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateB2BDeal extends CreateRecord
{
    protected static string $resource = B2BDealResource::class;

    public function getTitle(): string
    {
        return 'Create B2BDeal';
    }
}