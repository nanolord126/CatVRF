<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageSubscription\Pages;

use use App\Filament\Tenant\Resources\BeverageSubscriptionResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewBeverageSubscription extends ViewRecord
{
    protected static string $resource = BeverageSubscriptionResource::class;

    public function getTitle(): string
    {
        return 'View BeverageSubscription';
    }
}