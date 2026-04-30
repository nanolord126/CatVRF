<?php

declare(strict_types=1);

namespace App\Filament\Resources\CashbackRuleResource\Pages;

use App\Filament\Resources\CashbackRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateCashbackRule extends CreateRecord
{
    protected static string $resource = CashbackRuleResource::class;
}
