<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Finances\Pages;

use use App\Filament\Tenant\Resources\FinancesResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFinances extends EditRecord
{
    protected static string $resource = FinancesResource::class;

    public function getTitle(): string
    {
        return 'Edit Finances';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}