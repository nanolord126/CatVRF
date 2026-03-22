<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\CosmeticProductResource\Pages;

use App\Filament\Tenant\Resources\Beauty\CosmeticProductResource;
use Filament\Resources\Pages\ListRecords;

final class ListCosmeticProducts extends ListRecords
{
    protected static string $resource = CosmeticProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
