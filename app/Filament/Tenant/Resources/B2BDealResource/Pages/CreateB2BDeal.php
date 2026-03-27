<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\B2BDealResource\Pages;

use App\Filament\Tenant\Resources\B2BDealResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateB2BDeal extends CreateRecord
{
    protected static string $resource = B2BDealResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
