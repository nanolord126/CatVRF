<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Corporate\Pages;

use Modules\Fitness\Filament\Resources\Corporate\CorporatePackageResource;
use Filament\Resources\Pages\EditRecord;

final class EditCorporatePackage extends EditRecord
{
    protected static string $resource = CorporatePackageResource::class;
}
