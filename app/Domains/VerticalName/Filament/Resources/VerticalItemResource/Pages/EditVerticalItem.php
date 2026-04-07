<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Filament\Resources\VerticalItemResource\Pages;

use App\Domains\VerticalName\Filament\Resources\VerticalItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditVerticalItem extends EditRecord
{
    protected static string $resource = VerticalItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
