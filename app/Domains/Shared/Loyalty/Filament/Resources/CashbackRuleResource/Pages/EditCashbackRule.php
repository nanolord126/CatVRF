<?php

declare(strict_types=1);

namespace App\Domains\Shared\Loyalty\Filament\Resources\CashbackRuleResource\Pages;

use App\Domains\Shared\Loyalty\Filament\Resources\CashbackRuleResource;
use Filament\Resources\Pages\EditRecord;

final class EditCashbackRule extends EditRecord
{
    protected static string $resource = CashbackRuleResource::class;
}
