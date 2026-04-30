<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\RecurringSubscriptionResource\Pages;

use App\Domains\Payment\Filament\Resources\RecurringSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRecurringSubscription extends ViewRecord
{
    protected static string $resource = RecurringSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
