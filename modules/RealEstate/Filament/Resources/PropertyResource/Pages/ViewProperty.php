<?php declare(strict_types=1);

namespace Modules\RealEstate\Filament\Resources\PropertyResource\Pages;

use Modules\RealEstate\Filament\Resources\PropertyResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewProperty extends ViewRecord
{
    protected static string $resource = PropertyResource::class;
}
