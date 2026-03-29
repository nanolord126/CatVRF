<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FashionRetail\Pages;

use use App\Filament\Tenant\Resources\FashionRetailResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFashionRetail extends EditRecord
{
    protected static string $resource = FashionRetailResource::class;

    public function getTitle(): string
    {
        return 'Edit FashionRetail';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}