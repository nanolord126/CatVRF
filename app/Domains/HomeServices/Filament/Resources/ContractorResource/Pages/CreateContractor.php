<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ContractorResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ContractorResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateContractor extends CreateRecord
{
    protected static string $resource = ContractorResource::class;
}
