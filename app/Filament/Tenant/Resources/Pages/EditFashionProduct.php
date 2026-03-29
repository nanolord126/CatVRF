<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FashionProduct\Pages;

use use App\Filament\Tenant\Resources\FashionProductResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFashionProduct extends EditRecord
{
    protected static string $resource = FashionProductResource::class;

    public function getTitle(): string
    {
        return 'Edit FashionProduct';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}