<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Corporate\Pages;

use Modules\Fitness\Filament\Resources\Corporate\CorporateReportResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCorporateReport extends CreateRecord
{
    protected static string $resource = CorporateReportResource::class;
}
