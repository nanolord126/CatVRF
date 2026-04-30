<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Corporate\Pages;

use Modules\Fitness\Filament\Resources\Corporate\CorporateClientResource;
use Filament\Resources\Pages\EditRecord;

final class EditCorporateClient extends EditRecord
{
    protected static string $resource = CorporateClientResource::class;
}
