<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ContractorResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ContractorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditContractor extends EditRecord
{
    protected static string $resource = ContractorResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
