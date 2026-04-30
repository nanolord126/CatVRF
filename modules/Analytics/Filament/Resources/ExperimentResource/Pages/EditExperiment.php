<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Resources\ExperimentResource\Pages;

use Modules\Analytics\Filament\Resources\ExperimentResource;
use Filament\Resources\Pages\EditRecord;

class EditExperiment extends EditRecord
{
    protected static string $resource = ExperimentResource::class;
}
