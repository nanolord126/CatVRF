<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CorporateOrder\Pages;

use use App\Filament\Tenant\Resources\CorporateOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditCorporateOrder extends EditRecord
{
    protected static string $resource = CorporateOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit CorporateOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}