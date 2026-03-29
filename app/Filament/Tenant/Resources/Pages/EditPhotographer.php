<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Photographer\Pages;

use use App\Filament\Tenant\Resources\PhotographerResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditPhotographer extends EditRecord
{
    protected static string $resource = PhotographerResource::class;

    public function getTitle(): string
    {
        return 'Edit Photographer';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}