<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Stream\Pages;

use use App\Filament\Tenant\Resources\StreamResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditStream extends EditRecord
{
    protected static string $resource = StreamResource::class;

    public function getTitle(): string
    {
        return 'Edit Stream';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}