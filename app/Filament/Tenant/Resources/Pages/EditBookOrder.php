<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BookOrder\Pages;

use use App\Filament\Tenant\Resources\BookOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditBookOrder extends EditRecord
{
    protected static string $resource = BookOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit BookOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}