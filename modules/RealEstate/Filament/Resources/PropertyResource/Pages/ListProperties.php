<?php declare(strict_types=1);

namespace Modules\RealEstate\Filament\Resources\PropertyResource\Pages;

use Modules\RealEstate\Filament\Resources\PropertyResource;
use Filament\Resources\Pages\ListRecords;

final class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;
}
