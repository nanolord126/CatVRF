<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Listing\Pages;
use App\Filament\Tenant\Resources\ListingResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordListing extends ViewRecord {
    protected static string $resource = ListingResource::class;
}
