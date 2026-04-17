<?php declare(strict_types=1);

namespace Modules\RealEstate\Filament\Resources\PropertyResource\Pages;

use Modules\RealEstate\Filament\Resources\PropertyResource;
use Filament\Resources\Pages\EditRecord;

final class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;
}
