<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources\SubscriptionPaymentResource\Pages;

use App\Domains\Supermarket\Filament\Resources\SubscriptionPaymentResource;
use Filament\Resources\Pages\ListRecords;

final class ListSubscriptionPayments extends ListRecords
{
    protected static string $resource = SubscriptionPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Pages\Actions\CreateAction::make(),
        ];
    }
}
