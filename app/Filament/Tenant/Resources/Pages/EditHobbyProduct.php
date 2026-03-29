<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HobbyProduct\Pages;

use use App\Filament\Tenant\Resources\HobbyProductResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditHobbyProduct extends EditRecord
{
    protected static string $resource = HobbyProductResource::class;

    public function getTitle(): string
    {
        return 'Edit HobbyProduct';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}