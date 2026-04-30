<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\RecurringSubscriptionResource\Pages;

use App\Domains\Payment\Filament\Resources\RecurringSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRecurringSubscription extends CreateRecord
{
    protected static string $resource = RecurringSubscriptionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
