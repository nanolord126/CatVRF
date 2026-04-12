<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pet\Pages;

use App\Filament\Tenant\Resources\Pet\PetServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditService extends EditRecord
{
    protected static string $resource = PetServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
