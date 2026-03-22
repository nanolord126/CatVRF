<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\CosmeticProductResource\Pages;

use App\Filament\Tenant\Resources\Beauty\CosmeticProductResource;
use Filament\Resources\Pages\EditRecord;

final class EditCosmeticProduct extends EditRecord
{
    protected static string $resource = CosmeticProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
