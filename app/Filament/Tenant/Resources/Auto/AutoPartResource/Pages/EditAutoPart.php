<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages;

use App\Filament\Tenant\Resources\Auto\AutoPartResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditAutoPart extends EditRecord
{
    protected static string $resource = AutoPartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
