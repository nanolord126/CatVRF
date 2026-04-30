<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources\LabTestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Modules\Dental\Filament\Resources\LabTestResource;

final class CreateLabTest extends CreateRecord
{
    protected static string $resource = LabTestResource::class;
}
