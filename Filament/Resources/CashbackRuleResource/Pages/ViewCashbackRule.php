<?php

declare(strict_types=1);

namespace App\Filament\Resources\CashbackRuleResource\Pages;

use App\Filament\Resources\CashbackRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewCashbackRule extends ViewRecord
{
    protected static string $resource = CashbackRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
