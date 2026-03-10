<?php

namespace App\Filament\Admin\Resources\Geo\Pages;

use App\Filament\Admin\Resources\Geo\GeoHierarchyResource;
use Filament\Resources\Pages\ListRecords;

class ListGeo extends ListRecords
{
    protected static string $resource = GeoHierarchyResource::class;
}
