<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Books\Pages;

use use App\Filament\Tenant\Resources\BooksResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditBooks extends EditRecord
{
    protected static string $resource = BooksResource::class;

    public function getTitle(): string
    {
        return 'Edit Books';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}