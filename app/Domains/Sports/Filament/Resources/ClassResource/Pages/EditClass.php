<?php declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\ClassResource\Pages;

use App\Domains\Sports\Filament\Resources\ClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditClass extends EditRecord
{
    protected static string $resource = ClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
