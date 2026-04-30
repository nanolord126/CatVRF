<?php

declare(strict_types=1);

namespace App\Filament\Resources\CashbackRuleResource\Pages;

use App\Filament\Resources\CashbackRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditCashbackRule extends EditRecord
{
    protected static string $resource = CashbackRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
