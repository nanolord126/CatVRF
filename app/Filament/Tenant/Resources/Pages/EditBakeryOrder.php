<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BakeryOrder\Pages;

use use App\Filament\Tenant\Resources\BakeryOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditBakeryOrder extends EditRecord
{
    protected static string $resource = BakeryOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit BakeryOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}