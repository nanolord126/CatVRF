<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Cosmetics\Pages;

use use App\Filament\Tenant\Resources\CosmeticsResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditCosmetics extends EditRecord
{
    protected static string $resource = CosmeticsResource::class;

    public function getTitle(): string
    {
        return 'Edit Cosmetics';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}