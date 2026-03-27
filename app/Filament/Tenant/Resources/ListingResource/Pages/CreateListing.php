<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ListingResource\Pages;

use App\Filament\Tenant\Resources\ListingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateListing extends CreateRecord
{
    protected static string $resource = ListingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
