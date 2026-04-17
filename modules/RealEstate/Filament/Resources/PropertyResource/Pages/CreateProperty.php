<?php declare(strict_types=1);

namespace Modules\RealEstate\Filament\Resources\PropertyResource\Pages;

use Modules\RealEstate\Filament\Resources\PropertyResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;
}
