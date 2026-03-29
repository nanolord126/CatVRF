<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageOrder\Pages;

use use App\Filament\Tenant\Resources\BeverageOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditBeverageOrder extends EditRecord
{
    protected static string $resource = BeverageOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit BeverageOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}