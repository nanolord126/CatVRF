<?php

declare(strict_types=1);

namespace App\Domains\Education\Filament\Resources\CorporateContractResource\Pages;

use App\Domains\Education\Filament\Resources\CorporateContractResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCorporateContract extends CreateRecord
{
    protected static string $resource = CorporateContractResource::class;
}
