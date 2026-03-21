<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceCategoryResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditServiceCategory extends EditRecord
{
    protected static string $resource = ServiceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
