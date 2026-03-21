<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Cosmetics\CosmeticProductResource\Pages;

use App\Filament\Tenant\Resources\Cosmetics\CosmeticProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCosmeticProducts extends ListRecords
{
    protected static string $resource = CosmeticProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
