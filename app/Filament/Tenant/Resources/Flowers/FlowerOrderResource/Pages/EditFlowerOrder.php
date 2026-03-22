<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\FlowerOrderResource\Pages;

use App\Filament\Tenant\Resources\Flowers\FlowerOrderResource;
use Filament\Resources\Pages\EditRecord;

final class EditFlowerOrder extends EditRecord
{
    protected static string $resource = FlowerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
