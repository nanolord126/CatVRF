<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Book\Pages;

use use App\Filament\Tenant\Resources\BookResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditBook extends EditRecord
{
    protected static string $resource = BookResource::class;

    public function getTitle(): string
    {
        return 'Edit Book';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}