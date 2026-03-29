<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Fashion\Pages;

use use App\Filament\Tenant\Resources\FashionResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFashion extends EditRecord
{
    protected static string $resource = FashionResource::class;

    public function getTitle(): string
    {
        return 'Edit Fashion';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}