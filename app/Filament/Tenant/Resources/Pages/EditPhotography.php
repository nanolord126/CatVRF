<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Photography\Pages;

use use App\Filament\Tenant\Resources\PhotographyResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditPhotography extends EditRecord
{
    protected static string $resource = PhotographyResource::class;

    public function getTitle(): string
    {
        return 'Edit Photography';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}