<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Listing\Pages;
use App\Filament\Tenant\Resources\ListingResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsListing extends ListRecords {
    protected static string $resource = ListingResource::class;
}
