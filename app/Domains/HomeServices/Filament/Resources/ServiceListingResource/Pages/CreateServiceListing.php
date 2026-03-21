<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceListingResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceListingResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateServiceListing extends CreateRecord
{
    protected static string $resource = ServiceListingResource::class;
}
