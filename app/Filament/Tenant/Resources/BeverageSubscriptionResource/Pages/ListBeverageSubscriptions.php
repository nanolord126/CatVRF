<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageSubscriptionResource\Pages;

use App\Filament\Tenant\Resources\BeverageSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListBeverageSubscriptions extends ListRecords
{
    protected static string $resource = BeverageSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Grant New Subscription')
                ->icon('heroicon-o-sparkles'),
        ];
    }
}
