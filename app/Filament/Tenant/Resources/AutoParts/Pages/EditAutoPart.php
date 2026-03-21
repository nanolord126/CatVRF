<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoParts\Pages;

use App\Filament\Tenant\Resources\AutoParts\AutoPartResource;
use Filament\Resources\Pages\EditRecord;

final class EditAutoPart extends EditRecord
{
    protected static string $resource = AutoPartResource::class;
}
