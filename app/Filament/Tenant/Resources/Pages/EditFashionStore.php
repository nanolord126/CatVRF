<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FashionStore\Pages;

use use App\Filament\Tenant\Resources\FashionStoreResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFashionStore extends EditRecord
{
    protected static string $resource = FashionStoreResource::class;

    public function getTitle(): string
    {
        return 'Edit FashionStore';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}