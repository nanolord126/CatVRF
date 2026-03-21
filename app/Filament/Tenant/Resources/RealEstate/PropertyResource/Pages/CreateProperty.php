<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages;

use App\Filament\Tenant\Resources\RealEstate\PropertyResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;
}
