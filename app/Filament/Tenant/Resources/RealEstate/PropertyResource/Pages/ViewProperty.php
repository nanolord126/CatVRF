<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages;

use App\Filament\Tenant\Resources\RealEstate\PropertyResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewProperty extends ViewRecord
{
    protected static string $resource = PropertyResource::class;
}
