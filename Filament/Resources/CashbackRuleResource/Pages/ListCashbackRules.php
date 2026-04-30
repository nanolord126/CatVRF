<?php

declare(strict_types=1);

namespace App\Filament\Resources\CashbackRuleResource\Pages;

use App\Filament\Resources\CashbackRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCashbackRules extends ListRecords
{
    protected static string $resource = CashbackRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
