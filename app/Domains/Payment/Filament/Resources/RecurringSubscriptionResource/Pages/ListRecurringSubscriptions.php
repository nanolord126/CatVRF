<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\RecurringSubscriptionResource\Pages;

use App\Domains\Payment\Filament\Resources\RecurringSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecurringSubscriptions extends ListRecords
{
    protected static string $resource = RecurringSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
