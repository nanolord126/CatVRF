<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Listing\Pages;

use use App\Filament\Tenant\Resources\ListingResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateListing extends CreateRecord
{
    protected static string $resource = ListingResource::class;

    public function getTitle(): string
    {
        return 'Create Listing';
    }
}