<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatOrder\Pages;

use use App\Filament\Tenant\Resources\MeatOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditMeatOrder extends EditRecord
{
    protected static string $resource = MeatOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit MeatOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}